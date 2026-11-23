// ==========================
// MENU MOBILE
// ==========================

function toggleMenu(){
    document.getElementById("menu").classList.toggle("show");
}

// ==========================
// CARROSSEL DE IMAGENS (desliza para os lados)
// ==========================

const track = document.querySelector(".slider-track");
const slides = document.querySelectorAll(".slide");
const dots = document.querySelectorAll(".dot");
const next = document.querySelector(".next");
const prev = document.querySelector(".prev");

const total = slides.length;
let indice = 0;
let intervalo;

// Mostrar Slide

function mostrarSlide(n){

    track.style.transform = `translateX(-${n * (100 / total)}%)`;

    dots.forEach((dot) => dot.classList.remove("active"));
    dots[n].classList.add("active");
}

// Próximo Slide

function proximoSlide(){

    indice = (indice + 1) % total;
    mostrarSlide(indice);
}

// Slide Anterior

function slideAnterior(){

    indice = (indice - 1 + total) % total;
    mostrarSlide(indice);
}

// Iniciar Carrossel

function iniciarCarrossel(){

    intervalo = setInterval(() => {
        proximoSlide();
    }, 5000);
}

// Reiniciar Tempo

function reiniciar(){

    clearInterval(intervalo);
    iniciarCarrossel();
}

// Botão Direita

next.addEventListener("click", () => {
    proximoSlide();
    reiniciar();
});

// Botão Esquerda

prev.addEventListener("click", () => {
    slideAnterior();
    reiniciar();
});

// Clique nos indicadores

dots.forEach((dot, i) => {
    dot.addEventListener("click", () => {
        indice = i;
        mostrarSlide(indice);
        reiniciar();
    });
});

// Inicialização

mostrarSlide(indice);
iniciarCarrossel();
