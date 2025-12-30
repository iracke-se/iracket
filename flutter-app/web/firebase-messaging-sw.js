// Firebase Cloud Messaging Service Worker
importScripts('https://www.gstatic.com/firebasejs/10.7.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.7.0/firebase-messaging-compat.js');

// Initialize Firebase in the service worker
firebase.initializeApp({
  apiKey: 'AIzaSyByBDqubVveIYyas9K_9kQ_Ba8Y6_uP4z8',
  authDomain: 'iracket-se.firebaseapp.com',
  projectId: 'iracket-se',
  storageBucket: 'iracket-se.firebasestorage.app',
  messagingSenderId: '473107196258',
  appId: '1:473107196258:web:0c16fc9ad690e73188e43b'
});

// Retrieve an instance of Firebase Messaging
const messaging = firebase.messaging();

// Handle background messages
messaging.onBackgroundMessage((payload) => {
  console.log('Received background message:', payload);

  const notificationTitle = payload.notification?.title || 'New Notification';
  const notificationOptions = {
    body: payload.notification?.body || '',
    icon: '/icons/Icon-192.png'
  };

  self.registration.showNotification(notificationTitle, notificationOptions);
});
