let cart = [];

const html5QrCode = new Html5Qrcode("reader");

function onScanSuccess(decodedText, decodedResult) {
    // Si un produit est scanné, on fait une requête pour récupérer les infos du produit.
    fetch(`get_product_info.php?id=${decodedText}`)
        .then(response => response.json())
        .then(product => {
            if (product.error) {
                alert("Produit non trouvé !");
            } else {
                // Si le produit est trouvé, on l'ajoute au panier
                addToCart(product);
            }
        })
        .catch(error => console.error("Erreur lors de la récupération du produit:", error));
}

function onScanError(errorMessage) {
    console.log(errorMessage);
}

document.getElementById("start-scan").addEventListener("click", function() {
    // Le bouton commence le scan et démarre l'affichage de la caméra
    html5QrCode.start({ facingMode: "environment" }, { fps: 10, qrbox: 250 }, onScanSuccess, onScanError)
    .then(() => {
        document.getElementById("scan-status").textContent = "Scanner en cours...";
    })
    .catch(error => {
        console.log("Erreur lors du démarrage du scan: ", error);
        document.getElementById("scan-status").textContent = "Échec du démarrage du scan.";
    });
});

function addToCart(product) {
    const existingProduct = cart.find(item => item.id === product.id);
    if (existingProduct) {
        existingProduct.quantity++;
    } else {
        product.quantity = 1;
        cart.push(product);
    }
    updateCartDisplay();
}

function updateCartDisplay() {
    const cartItemsContainer = document.getElementById("cart-items");
    cartItemsContainer.innerHTML = "";
    let totalPrice = 0;

    cart.forEach(product => {
        totalPrice += product.prix * product.quantity;
        const row = document.createElement("tr");

        row.innerHTML = `
            <td><img src="${product.image}" alt="${product.nom}" width="50"></td>
            <td>${product.nom}</td>
            <td>${product.prix} €</td>
            <td><input type="number" value="${product.quantity}" min="1" onchange="updateQuantity(${product.id}, this.value)"></td>
            <td>${(product.prix * product.quantity).toFixed(2)} €</td>
            <td><span class="delete-icon" onclick="removeProduct(${product.id})">❌</span></td>
        `;
        cartItemsContainer.appendChild(row);
    });

    document.getElementById("total-price").textContent = totalPrice.toFixed(2);
}

function updateQuantity(productId, quantity) {
    const product = cart.find(item => item.id === productId);
    if (product) {
        product.quantity = parseInt(quantity);
        updateCartDisplay();
    }
}

function removeProduct(productId) {
    cart = cart.filter(item => item.id !== productId);
    updateCartDisplay();
}

document.getElementById("clear-cart").addEventListener("click", function() {
    cart = [];
    updateCartDisplay();
});
