<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FCM Token Test</title>
</head>
<body>

<h2>Firebase FCM Test</h2>

<button onclick="getFCMToken()">
    Get FCM Token
</button>

<br><br>

<textarea
    id="token"
    rows="10"
    cols="120"
    placeholder="FCM token will appear here">
</textarea>

<script type="module">

import { initializeApp }
from "https://www.gstatic.com/firebasejs/10.13.2/firebase-app.js";

import {
    getMessaging,
    getToken
}
from "https://www.gstatic.com/firebasejs/10.13.2/firebase-messaging.js";

const firebaseConfig = {
    apiKey: "AIzaSyAMYtqhWQ8v-uHiM07rXtxi3hRusTzlNwI",
    authDomain: "ecommerce-71257.firebaseapp.com",
    projectId: "ecommerce-71257",
    storageBucket: "ecommerce-71257.firebasestorage.app",
    messagingSenderId: "409028432262",
    appId: "1:409028432262:web:4a0b8396022f8724131c07",
    measurementId: "G-W1QCKXG332"
};

const app = initializeApp(firebaseConfig);

const messaging = getMessaging(app);

window.getFCMToken = async function () {

    try {

        const permission =
            await Notification.requestPermission();

        if (permission !== 'granted') {
            alert('Notification permission denied');
            return;
        }

        const registration =
            await navigator.serviceWorker.register(
                '/firebase-messaging-sw.js'
            );

        const currentToken =
            await getToken(messaging, {
                vapidKey:
                "BGF5k_jlbWYv2sHC2lfRnTFk3IbrQUkUTXLO0KVV3iMGJQk_0MVVYbC0c7dnqHEmnkBqK4RlXxigJUJYQjZQ2EI",
                serviceWorkerRegistration: registration
            });

        if (currentToken) {

            document.getElementById('token').value =
                currentToken;

            console.log(currentToken);

        } else {

            alert('No registration token available');
        }

    } catch (error) {

        console.error(error);

        alert(error.message);
    }
};

</script>

</body>
</html>