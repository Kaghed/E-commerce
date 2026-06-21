importScripts('https://www.gstatic.com/firebasejs/10.13.2/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.13.2/firebase-messaging-compat.js');

firebase.initializeApp({
    apiKey: "AIzaSyAMYtqhWQ8v-uHiM07rXtxi3hRusTzlNwI",
    authDomain: "ecommerce-71257.firebaseapp.com",
    projectId: "ecommerce-71257",
    storageBucket: "ecommerce-71257.firebasestorage.app",
    messagingSenderId: "409028432262",
    appId: "1:409028432262:web:4a0b8396022f8724131c07"
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage(function(payload) {
    console.log('Background message:', payload);

    self.registration.showNotification(
        payload.notification?.title || 'Notification',
        {
            body: payload.notification?.body || '',
            icon: '/favicon.ico'
        }
    );
});