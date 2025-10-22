const express = require('express');
const mysql = require('mysql');
const bodyParser = require('body-parser');
const axios = require('axios');  // Pour appeler l'API de reconnaissance d'image (Google Vision, etc.)
const app = express();

// Configurer le serveur pour utiliser le corps des requêtes en JSON
app.use(bodyParser.json());

// Connexion à la base de données MySQL
const db = mysql.createConnection({
  host: 'localhost',
  user: 'votre_utilisateur',    // Remplacez par votre utilisateur MySQL
  password: 'votre_mot_de_passe',  // Remplacez par votre mot de passe MySQL
  database: 'produits_db'
});

// Vérifier la connexion à la base de données
db.connect(err => {
  if (err) {
    console.error('Erreur de connexion à la base de données:', err);
    return;
  }
  console.log('Connexion à la base de données réussie');
});

// Endpoint pour traiter l'image et identifier les produits
app.post('/reconnaissance-objets', async (req, res) => {
  try {
    const imageData = req.body.image;
    
    // Utilisation d'un service de reconnaissance d'objets (ex : Google Vision API)
    // Remplacez par l'appel approprié à un service externe pour analyser l'image
    const result = await axios.post('https://vision.googleapis.com/v1/images:annotate', {
      requests: [
        {
          image: {
            content: imageData.split(',')[1]  // Retirer la partie 'data:image/png;base64,' du Data URL
          },
          features: [
            { type: 'LABEL_DETECTION', maxResults: 10 }
          ]
        }
      ]
    }, {
      headers: {
        'Authorization': `Bearer VOTRE_CLE_API_GOOGLE` // Remplacez par votre clé API Google Vision
      }
    });

    const objetsDetectes = result.data.responses[0].labelAnnotations.map(label => ({
      nom: label.description
    }));

    // Renvoyer les objets détectés
    res.json({ objets: objetsDetectes });
  } catch (err) {
    console.error(err);
    res.status(500).send('Erreur lors de la reconnaissance d\'image');
  }
});

// Endpoint pour récupérer les informations d'un produit par son nom
app.get('/produit/:nom', (req, res) => {
  const nomProduit = req.params.nom;

  db.query('SELECT * FROM produits WHERE nom = ?', [nomProduit], (err, results) => {
    if (err) {
      console.error(err);
      return res.status(500).send('Erreur lors de la récupération du produit');
    }

    if (results.length === 0) {
      return res.status(404).send('Produit non trouvé');
    }

    // Renvoyer les informations du produit
    const produit = results[0];
    res.json({
      nom: produit.nom,
      prix: produit.prix
    });
  });
});

// Lancer le serveur sur le port 3000
app.listen(3000, () => {
  console.log('Serveur en écoute sur le port 3000');
});
