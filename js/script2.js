let navbar = document.querySelector('.header .flex .navbar');

document.querySelector('#menu-btn').onclick = () =>{
   navbar.classList.toggle('active');
}

let account = document.querySelector('.user-account');

document.querySelector('#user-btn').onclick = () =>{
   account.classList.add('active');
}

document.querySelector('#close-account').onclick = () =>{
   account.classList.remove('active');
}

let myOrders = document.querySelector('.my-orders');

document.querySelector('#order-btn').onclick = () =>{
   myOrders.classList.add('active');
}

document.querySelector('#close-orders').onclick = () =>{
   myOrders.classList.remove('active');
}

let cart = document.querySelector('.shopping-cart');

document.querySelector('#cart-btn').onclick = () =>{
   cart.classList.add('active');
}

document.querySelector('#close-cart').onclick = () =>{
   cart.classList.remove('active');
}

window.onscroll = () =>{
   navbar.classList.remove('active');
   myOrders.classList.remove('active');
   cart.classList.remove('active');
};

let slides = document.querySelectorAll('.home-bg .home .slide-container .slide');
let index = 0;

function next(){
   slides[index].classList.remove('active');
   index = (index + 1) % slides.length;
   slides[index].classList.add('active');
}

function prev(){
   slides[index].classList.remove('active');
   index = (index - 1 + slides.length) % slides.length;
   slides[index].classList.add('active');
}

let accordion = document.querySelectorAll('.faq .accordion-container .accordion');

accordion.forEach(acco =>{
   acco.onclick = () =>{
      accordion.forEach(remove => remove.classList.remove('active'));
      acco.classList.add('active');

   }
  

//começa o teste do carrinho
let navbar = document.querySelector('.header .flex .navbar');
let account = document.querySelector('.user-account');
let myOrders = document.querySelector('.my-orders');
let cart = document.querySelector('.shopping-cart');
let cartItems = [];
let totalItemsInCart = 0; // Variável para armazenar o total de itens no carrinho

function addToCart(itemName, price, quantity) {
    for (let i = 0; i < quantity; i++) {
        cartItems.push({ name: itemName, price: price });
        totalItemsInCart += 1; // Incrementa o contador de itens no carrinho
    }
    updateCart();
    updateTotalPrice(); // Atualiza o preço total na interface
    updateCartCounter(); // Atualiza o contador na interface
}

function updateCart() {
    let cartItemsDiv = document.querySelector('.shopping-cart section');
    cartItemsDiv.innerHTML = '';

    cartItems.forEach(item => {
        let cartItemDiv = document.createElement('div');
        cartItemDiv.innerHTML = `
            <a href="#" class="fas fa-times"></a>
            <img src="images/${item.name.toLowerCase().replace(' ', '-')}.jpg" alt="">
            <div class="content">
                <p style="font-size: 18px;">${item.name}<span class="price">( R$${item.price.toFixed(2)} )</span></p>
            </div>
        `;
        cartItemsDiv.appendChild(cartItemDiv);
    });

    // Adiciona o elemento que exibe o total por último
    let totalDiv = document.createElement('div');
    totalDiv.innerHTML = `
    <p style="font-size: 24px;">Total<span class="price">( R$${calculateTotalPrice().toFixed(2)} )</span></p>
    `

    cartItemsDiv.appendChild(totalDiv);
}

function calculateTotalPrice() {
    return cartItems.reduce((total, item) => total + item.price, 0);
}

function updateTotalPrice() {
    let totalPrice = calculateTotalPrice();
    let totalPriceSpan = document.querySelector('.price');
}

function updateCartCounter() {
    let cartCounter = document.querySelector('#cart-counter');
    cartCounter.textContent = totalItemsInCart;
}

document.querySelector('#cart-btn').onclick = () => {
    cart.classList.add('active');
    updateCart();
    updateTotalPrice();
};

document.querySelector('#close-cart').onclick = () => {
    cart.classList.remove('active');
};

document.querySelectorAll('.menu .box form').forEach(form => {
    form.onsubmit = (event) => {
        event.preventDefault(); 
        let itemName = form.parentNode.querySelector('.name').innerText;
        let itemPrice = parseFloat(form.parentNode.querySelector('.price span').innerText);
        let quantity = parseInt(form.querySelector('.qty').value);
        addToCart(itemName, itemPrice, quantity);
    };
});



let shoppingCart = document.querySelector('.shopping-cart');


let redirectButton = document.querySelector('.redirect-button');
if (!redirectButton) {
    redirectButton = document.createElement('button');
    redirectButton.textContent = 'Ir para página de pagamento';
    redirectButton.classList.add('redirect-button', 'btn'); 

 
    redirectButton.style.padding = '10px 20px'; 
    redirectButton.style.border = 'none'; // 

    shoppingCart.appendChild(redirectButton);
}


function redirectToPaymentPage() {
    window.location.href = 'pedidos.php'; 
}


redirectButton.addEventListener('click', redirectToPaymentPage);

  }


);