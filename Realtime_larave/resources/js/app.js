import './bootstrap';

Echo.private("notifications")
    .listen("UserSessionChange", e => {
        console.log({ e });
        const notiElement = document.querySelector("#notification")
        notiElement.innerText = e.message
        notiElement.classList.remove("invisible")
        notiElement.classList.remove("alert-success")
        notiElement.classList.remove("alert-danger")
        notiElement.classList.add('alert-' + e.type)

    })