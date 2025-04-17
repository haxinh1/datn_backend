from flask import Flask, request, jsonify, Response
import pandas as pd
import mysql.connector
import json
import decimal
import numpy as np
import requests
from PIL import Image
from io import BytesIO
from tensorflow.keras.applications.resnet50 import ResNet50, preprocess_input
from tensorflow.keras.preprocessing import image as keras_image

app = Flask(__name__)

# Cấu hình DB
db_config = {
    "host": "localhost",
    "user": "root",
    "password": "",
    "database": "datn2025"
}

resnet_model = ResNet50(weights="imagenet", include_top=False, pooling="avg")

def decimal_to_float(obj):
    if isinstance(obj, decimal.Decimal):
        return float(obj)
    raise TypeError(f"Object of type {type(obj)} is not JSON serializable")

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

# API: POST /search-image
@app.route('/search-image', methods=['POST'])
def search_image():
    image_file = request.files.get('image')
    if not image_file:
        return jsonify({"error": "No image uploaded"}), 400

    temp_path = 'temp_image.jpg'
    image_file.save(temp_path)

    recommendations = search_similar_images(temp_path, threshold=20.0)

    if not recommendations:
        return jsonify({"message": "Không tìm thấy sản phẩm tương tự"}), 404

    return Response(
        json.dumps({"recommended_products": recommendations}, ensure_ascii=False, default=decimal_to_float),
        content_type="application/json; charset=utf-8"
    )

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)
