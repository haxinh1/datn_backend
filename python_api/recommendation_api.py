from flask import Flask, request, jsonify, Response
import pandas as pd
import mysql.connector
import json
import decimal
from collections import Counter

app = Flask(__name__)

db_config = {
    "host": "localhost",
    "user": "root", 
    "password": "", 
    "database": "datn2025" 
}

def decimal_to_float(obj):
    if isinstance(obj, decimal.Decimal):
        return float(obj)
    raise TypeError(f"Object of type {type(obj)} is not JSON serializable")

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

@app.route('/recommend', methods=['GET'])
def recommend():
    product_id = request.args.get('product_id', type=int)
    
    if not product_id:
        return jsonify({"error": "Missing product_id"}), 400

    recommendations = recommend_products(product_id)
    return Response(
        json.dumps({"recommended_products": recommendations}, ensure_ascii=False, default=decimal_to_float),
        content_type="application/json; charset=utf-8"
    )

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)
