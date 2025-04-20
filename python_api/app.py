from flask import Flask, request, jsonify, Response
import pandas as pd
import mysql.connector
import json
import decimal
import numpy as np
import requests
from PIL import Image
from io import BytesIO
from collections import Counter
from tensorflow.keras.applications.resnet50 import ResNet50, preprocess_input
from tensorflow.keras.preprocessing import image as keras_image

app = Flask(__name__)

# Cấu hình database
db_config = {
    "host": "localhost",
    "user": "root",
    "password": "",
    "database": "datn2025"
}

# Model ResNet50
resnet_model = ResNet50(weights="imagenet", include_top=False, pooling="avg")

# Hàm convert Decimal về float để JSON không lỗi
def decimal_to_float(obj):
    if isinstance(obj, decimal.Decimal):
        return float(obj)
    raise TypeError(f"Object of type {type(obj)} is not JSON serializable")

# =====================
# ===== API 1: Recommend theo product_id
# =====================

def get_orders_data():
    connection = mysql.connector.connect(**db_config)
    cursor = connection.cursor(dictionary=True)
    cursor.execute("""
        SELECT orders.user_id, order_items.order_id, order_items.product_id
        FROM orders
        JOIN order_items ON orders.id = order_items.order_id
        WHERE orders.status_id = 7
    """)
    orders = cursor.fetchall()
    cursor.close()
    connection.close()
    return orders

def recommend_products(product_id, top_n=2):
    orders_data = get_orders_data()
    if not orders_data:
        return []

    df = pd.DataFrame(orders_data)
    relevant_orders = df[df["product_id"] == product_id]["order_id"].unique()
    related_products = df[df["order_id"].isin(relevant_orders) & (df["product_id"] != product_id)]["product_id"]
    product_counts = Counter(related_products)
    recommended_product_ids = [p[0] for p in product_counts.most_common(top_n)]

    if not recommended_product_ids:
        return []

    connection = mysql.connector.connect(**db_config)
    cursor = connection.cursor(dictionary=True)

    format_strings = ','.join(['%s'] * len(recommended_product_ids))
    query = f"""
        SELECT id, name, thumbnail, sell_price, sale_price
        FROM products
        WHERE id IN ({format_strings})
    """
    cursor.execute(query, tuple(recommended_product_ids))
    products = cursor.fetchall()
    cursor.close()
    connection.close()
    return products

def get_variants_and_attributes(product_id):
    """ Lấy thông tin về các biến thể và thuộc tính của sản phẩm """
    connection = mysql.connector.connect(**db_config)
    cursor = connection.cursor(dictionary=True)
    query = """
        SELECT 
            product_variants.id AS variant_id, 
            product_variants.thumbnail AS variant_thumbnail, 
            product_variants.sell_price AS variant_sell_price, 
            product_variants.sale_price AS variant_sale_price,
            attribute_values.value AS attribute_value
        FROM product_variants
        JOIN attribute_value_product_variants ON product_variants.id = attribute_value_product_variants.product_variant_id
        JOIN attribute_values ON attribute_value_product_variants.attribute_value_id = attribute_values.id
        WHERE product_variants.product_id = %s
    """
    cursor.execute(query, (product_id,))
    variants = cursor.fetchall()
    cursor.close()
    connection.close()

    # Gộp các attribute_values vào mảng attributes cho từng variant
    variant_dict = {}
    for variant in variants:
        variant_id = variant['variant_id']
        if variant_id not in variant_dict:
            variant_dict[variant_id] = {
                "variant_id": variant_id,
                "variant_thumbnail": variant['variant_thumbnail'],
                "variant_sell_price": variant['variant_sell_price'],
                "variant_sale_price": variant['variant_sale_price'],
                "attributes": []
            }
        variant_dict[variant_id]["attributes"].append(variant['attribute_value'])

    return list(variant_dict.values())

@app.route('/recommend', methods=['GET'])
def recommend():
    product_id = request.args.get('product_id', type=int)
    if not product_id:
        return jsonify({"error": "Missing product_id"}), 400

    recommendations = recommend_products(product_id)
    if not recommendations:
        return jsonify({"message": "Không tìm thấy sản phẩm tương tự"}), 404

    recommended_products = []
    for product in recommendations:
        product_data = {
            "product_id": product['id'],
            "product_name": product['name'],
            "product_thumbnail": product['thumbnail'],
            "product_sell_price": product['sell_price'],
            "product_sale_price": product['sale_price'],
            "variants": get_variants_and_attributes(product['id'])
        }
        recommended_products.append(product_data)

    return Response(
        json.dumps({"recommended_products": recommended_products}, ensure_ascii=False, default=decimal_to_float),
        content_type="application/json; charset=utf-8"
    )

# =====================
# ===== API 2: Tìm kiếm theo hình ảnh
# =====================

def get_products_data():
    connection = mysql.connector.connect(**db_config)
    cursor = connection.cursor(dictionary=True)
    cursor.execute("SELECT id, name, thumbnail, sell_price, sale_price FROM products")
    products = cursor.fetchall()
    cursor.close()
    connection.close()
    return products

def open_image_from_url(url):
    response = requests.get(url, timeout=10)
    img = Image.open(BytesIO(response.content)).convert("RGB")
    return img

def process_image(img_input):
    try:
        if isinstance(img_input, str) and img_input.startswith("http"):
            img = open_image_from_url(img_input)
        else:
            img = Image.open(img_input).convert("RGB")

        img = img.resize((224, 224))
        img_array = keras_image.img_to_array(img)
        img_array = np.expand_dims(img_array, axis=0)
        img_array = preprocess_input(img_array)

        features = resnet_model.predict(img_array)
        return features.flatten()
    except Exception as e:
        print(f"[ERROR] Lỗi xử lý ảnh: {e}")
        return None

def search_similar_images(input_image_path, top_n=5, threshold=50.0):
    input_image_features = process_image(input_image_path)
    if input_image_features is None:
        return []

    products = get_products_data()
    similarities = []

    for product in products:
        try:
            product_image_url = product['thumbnail']
            product_features = process_image(product_image_url)
            if product_features is None:
                continue

            similarity = np.linalg.norm(input_image_features - product_features)
            print(f"[DEBUG] {product['name']} - Distance: {similarity:.2f}")

            similarities.append((product, similarity))
        except Exception as e:
            print(f"[ERROR] Lỗi với sản phẩm {product['id']}: {e}")
            continue

    filtered = [item for item in similarities if item[1] < threshold]
    filtered.sort(key=lambda x: x[1])
    top_products = [item[0] for item in filtered[:top_n]]

    return top_products

@app.route('/search-image', methods=['POST'])
def search_image():
    image_file = request.files.get('image')
    if not image_file:
        return jsonify({"error": "No image uploaded"}), 400

    temp_path = 'temp_image.jpg'
    image_file.save(temp_path)

    recommendations = search_similar_images(temp_path, threshold=30.0)
    if not recommendations:
        return jsonify({"message": "Không tìm thấy sản phẩm tương tự"}), 404

    recommended_products = []
    for product in recommendations:
        product_data = {
            "product_id": product['id'],
            "product_name": product['name'],
            "product_thumbnail": product['thumbnail'],
            "product_sell_price": product['sell_price'],
            "product_sale_price": product['sale_price'],
            "variants": get_variants_and_attributes(product['id'])
        }
        recommended_products.append(product_data)

    return Response(
        json.dumps({"recommended_products": recommended_products}, ensure_ascii=False, default=decimal_to_float),
        content_type="application/json; charset=utf-8"
    )


if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)
