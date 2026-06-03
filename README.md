# ⚡ SkillSwap — Share Skills, Build Connections, Grow Together

> *"Knowledge increases by sharing but not by saving."*

SkillSwap is a web-based platform where people can exchange skills with each other for free — no money involved. If you know something someone else wants to learn, and they know something you want to learn, you swap. Simple as that.

---

## 👥 Team — Agile Dynamics

| Name | Role | Component |
|---|---|---|
| Asikur Rahman | Developer | Chat & Messaging |
| Imran Hossain | Developer | User & Authentication |
| Kritika Singh | Scrum Master | Search & Matchmaking |
| Radiah Anan | Developer | Skill Management |
| Jiasmin | Developer | Engagement & Feedback |
| Sudikshya | Developer | Session Management |

---

## ✨ Features

- 🔐 **User Registration & Login** — Secure authentication with hashed passwords and password reset via email
- 👤 **Profile Management** — Users can set up profiles with skills, bio, location, and profile picture
- 🔍 **Search & Matchmaking** — Find users by skill and location; get notified when a match is found
- 💬 **Chat & Messaging** — Private messaging between matched users with file sharing support
- 📅 **Events** — Create and join local skill-swap events
- 🗓️ **Session Management** — Schedule and track skill swap sessions (Pending / Accepted / Rejected)
- ⭐ **Ratings & Feedback** — Rate swap partners after sessions to build trust in the community
- 📞 **Video Calling** — WebRTC-based peer-to-peer video calls between matched users

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Frontend | HTML, CSS |
| Backend | PHP |
| Database | MySQL |
| Server | Apache via XAMPP |
| Code Editor | VS Code |
| Version Control | Git & GitHub |

---

## 🗄️ Database

The system uses a MySQL database named `skillswap` with the following tables:

| Table | Description |
|---|---|
| `users` | User accounts and profile info |
| `messages` | Chat messages between users |
| `matches` | Skill swap matches between users |
| `swaps` | Swap requests and their status |
| `sessions` | Scheduled skill swap sessions |
| `user_skills` | Skills each user can teach or wants to learn |
| `events` | Community events |
| `event_participant` | Event attendance |
| `rating` | User ratings after swaps |
| `notification` | In-app notifications |
| `call_signals` | WebRTC signalling data |
| `password_resets` | Password reset tokens |

---

## 💬 Chat & Messaging Module

*Developed by: Asikur Rahman*

The messaging module is the communication layer of the platform. Once two users are matched, they get a private space to organise their skill swap.

**Key functionality:**
- Send and receive direct messages
- Mark messages as read/unread
- Edit and delete messages
- Share files and images in chat
- Conversation history stored and retrieved in correct order
- Real-time message polling

**Database table: `messages`**

| Column | Type | Description |
|---|---|---|
| `MessageID` | INT (PK) | Unique message identifier |
| `MessageText` | VARCHAR(255) | Text content of the message |
| `IsRead` | BOOLEAN | 0 = unread, 1 = read |
| `Timestamp` | DATETIME | When the message was sent |
| `sender_id` | INT (FK) | User who sent the message |
| `receiver_id` | INT (FK) | User who received the message |
| `file_path` | VARCHAR(500) | Path to attached file (if any) |
| `file_name` | VARCHAR(255) | Original filename |
| `file_type` | VARCHAR(100) | MIME type of attached file |
| `file_size` | BIGINT | File size in bytes |

---

## 🚀 Installation & Setup

### Requirements

- XAMPP (PHP + Apache + MySQL)
- Git

### Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/rahmanasikur106-dotcom/Skill_Swap.git
   ```

2. **Move to XAMPP htdocs**
   ```
   C:/xampp/htdocs/skillswap
   ```

3. **Import the database**
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Create a new database called `skillswap`
   - Import the `skillswap.sql` file

4. **Configure the database connection**

   Open `db.php` and update your credentials if needed:
   ```php
   $host   = "localhost";
   $user   = "root";
   $pass   = "";
   $dbname = "skillswap";
   ```

5. **Run the app**
   - Start Apache and MySQL in XAMPP
   - Visit: `http://localhost/skillswap`

---

## 📁 Project Structure

```
skillswap/
├── index.php              # Landing page
├── login.php              # Login page
├── register.php           # Registration page
├── dashboard.php          # Main dashboard
├── chat.php               # Chat & messaging
├── call.php               # WebRTC video/audio calls
├── profile.php            # User profile
├── discovery.php          # Skill discovery / search
├── matchmaking.php        # Match logic
├── matches.php            # Confirmed matches
├── session_list.php       # Skill swap sessions
├── create_session.php     # Schedule a session
├── swaps.php              # Swap requests
├── notification.php       # Notifications
├── create_event.php       # Event pages
├── includes/              # Header & footer
├── images/                # Static assets
├── style.css              # Global stylesheet
├── db.php                 # Database connection
└── skillswap.sql          # Database dump
```

---

## 🧪 Running Tests

```bash
composer install
./vendor/bin/phpunit --configuration phpunit.xml
```

Test coverage includes user authentication, chat messaging, events, notifications, sessions, skill management, and feedback/ratings.

---

📌 *Module: CTEC2713 — Agile Development Team Project*  
*Faculty of Computing, Engineering & Media*
