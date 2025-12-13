# PingMe — Real-Time Communication System

A real-time chat application built to demonstrate **event-driven architecture**, WebSocket communication, and scalable backend design using Laravel Reverb.

This project focuses on **correctness, system design, and realtime behavior**, not UI polish.

---

## 🎯 Purpose

The main goals of this project are to:

- Practice real-time communication patterns
- Design an event-driven backend with WebSockets
- Handle presence, typing indicators, and async notifications
- Demonstrate clean separation between realtime, domain logic, and UI

---

## ✨ Key Features

- Real-time messaging using WebSockets (Laravel Reverb)
- Online / offline presence tracking
- Typing indicators
- Image attachments with preview
- Browser & audio notifications
- Unread message indicators
- Dark mode support
- Responsive layout using TailwindCSS

---

## 🏗️ Architecture Overview

- Backend-driven architecture using Inertia.js
- Event-driven messaging (Events → Broadcast → UI)
- WebSocket layer isolated from domain logic
- Async processing for notifications using queues
- Clean separation between:
  - Conversations
  - Messages
  - Presence
  - Notifications

---

## 🧰 Tech Stack

- **Backend**: Laravel 12 (PHP 8.3+)
- **Frontend**: Vue 3 + Inertia.js + Vite
- **Realtime**: Laravel Reverb (WebSockets)
- **Async**: Laravel Queues
- **Styling**: TailwindCSS
- **Database**: MySQL (SQLite supported locally)

---

## 🚀 Getting Started

### Prerequisites

- PHP 8.3+
- Composer
- Node.js & npm
- MySQL

---

### Installation

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link
```

### Realtime Configuration (.env)
```bash
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=local-app-id
REVERB_APP_KEY=local-app-key
REVERB_APP_SECRET=local-app-secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### ▶️ Running the Application

Open three terminals:
- Laravel server
  
```bash
php artisan serve
```

- Reverb WebSocket server
```bash
php artisan reverb:start
```
- Queue worker
```bash
php artisan queue:work
```
- Frontend (optional)
```bash
npm run dev
```

- Visit http://localhost:8000

### 📌 Design Decisions & Trade-offs

Chose Laravel Reverb to stay fully backend-driven

Avoided external realtime services to understand WebSocket internals

Kept UI minimal to focus on realtime correctness

Notifications handled asynchronously for scalability

### 🔮 Possible Improvements

Read receipts and delivery status

Message pagination & virtualization

Horizontal scaling for WebSocket servers

Automated tests for realtime flows

### 📄 License

MIT License — for learning and portfolio purposes.
ذذذ
