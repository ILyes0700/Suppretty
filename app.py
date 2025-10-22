from flask import Flask, request, jsonify
import mysql.connector
import base64
import os
from google.cloud import vision

app = Flask(__name__)

# Configuration de la base de données
db_config = {
    'host': 'localhost',
    'user': 'root',
    'password': 'root',  # Remplace par ton mot de passe
    'database': 'produits_db'
}

# Configuration de Google Vision
os.environ['GOOGLE_APPLICATION_CREDENTIALS'] = 'chemin/vers/ton/fichier/credentials.json'  # Remplace par ton fichier de credentials

# Fonction pour détecter les produits dans une image
def detect_products(image_content):
    client = vision.ImageAnnotatorClient()
    image = vision.Image(content=image_content)
    response = client.object_localization(image=image)
    return response.localized_object_annotations

# Route pour traiter l'image
@app.route('/process-image', methods=['POST'])
def process_image():
    data = request.json
    image_data = data['image'].split(',')[1]  # Supprimer le préfixe "data:image/jpeg;base64,"
    image_content = base64.b64decode(image_data)

    # Détecter les produits dans l'image
    detected_products = detect_products(image_content)

    # Interroger la base de données pour chaque produit détecté
    results = []
    connection = mysql.connector.connect(**db_config)
    cursor = connection.cursor(dictionary=True)
    for product in detected_products:
        product_name = product.name
        cursor.execute("SELECT nom, prix FROM produits WHERE nom = %s", (product_name,))
        product_data = cursor.fetchone()
        if product_data:
            results.append({"nom": product_data['nom'], "prix": product_data['prix']})
    cursor.close()
    connection.close()

    return jsonify(results)

if __name__ == '__main__':
    app.run(debug=True)