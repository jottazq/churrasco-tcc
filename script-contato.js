emailjs.init("vtdnR8mM5HR2eidRf");


document
.getElementById("form-contato")
.addEventListener("submit", function(event){

    event.preventDefault();


    emailjs.send(
        "service_9141xbm",
        "template_r1667gh",
        {

            nome: document.getElementById("nome").value,

            telefone: document.getElementById("telefone").value,

            email: document.getElementById("email").value,

            mensagem: document.getElementById("mensagem").value

        }

    )

    .then(function(){

        alert("Mensagem enviada com sucesso! Em breve entraremos em contato.");

        document
        .getElementById("form-contato")
        .reset();

    })


    .catch(function(error){

        alert("Erro ao enviar mensagem. Tente novamente.");

        console.log(error);

    });


});