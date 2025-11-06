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

// Função para mostrar a mensagem de aviso
function showLoginAlert(event) {
    // Mostra a mensagem de aviso
    alert('Faça o seu login primeiro e tente adicionar os itens ao carrinho.');
    event.preventDefault(); // Impede o envio do formulário
}

// Seleciona todos os botões "adicionar ao carrinho"
let addToCartButtons = document.querySelectorAll('.box form .btn');

// Adiciona o evento de clique apenas se ainda não foi adicionado
addToCartButtons.forEach(button => {
    if (!button.hasAttribute('data-event-added')) {
        button.addEventListener('click', showLoginAlert);
        button.setAttribute('data-event-added', 'true'); // Adiciona um atributo para indicar que o evento foi adicionado
    }
});



//
  
  }


);