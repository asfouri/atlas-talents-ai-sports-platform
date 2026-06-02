# 🏆 Atlas Talents — AI Sports Talent Detection Platform

<div align="center">

![Atlas Talents Hero](screenshots/01-landing-hero.png)

### 🇲🇦 AI-powered platform for detecting, tracking, and recruiting young sports talents in Morocco.

**Atlas Talents** connects PE teachers, students, coaches, recruiters, clubs, and recruitment managers through a modern web platform powered by AI-assisted video analysis.

</div>

---

## 📌 Project Overview

**Atlas Talents** is a PHP/MySQL web application designed to help sports organizations identify promising young athletes through uploaded performance videos, AI-assisted scoring, dashboards, messaging, and role-based recruitment workflows.

The platform allows teachers to upload student performance videos, generate AI-based athletic evaluations, track student progress, and make talented athletes visible to recruiters and clubs. Recruiters can discover talents, filter profiles, save favorites, and coordinate with coaches and managers.

---

## 🚀 Key Features

### 🤖 AI-Assisted Video Analysis

- Upload sports performance videos
- Extract key performance indicators
- Generate AI-based global score
- Evaluate multiple physical criteria:
  - Speed
  - Coordination
  - Endurance
  - Strength
  - Flexibility
- Generate strengths, weaknesses, recommendations, and action plans
- Display AI confidence score and performance summary

### 👥 Role-Based Dashboards

The platform includes multiple user spaces:

- **Student**
- **PE Teacher**
- **Coach**
- **Recruiter / Club**
- **Recruitment Manager**
- **Admin / Management access**

Each role has its own dashboard, navigation, data visibility, and workflow.

### 📊 Progress Tracking

- Student performance evolution
- Score history
- Personal recommendations
- Coach monitoring dashboard
- Class statistics
- Talent progression charts

### 🔎 Recruitment System

- Talent discovery dashboard
- Talent shortlist
- Recruiter favorites
- City and sport filtering
- Recruitment priority indicators
- Geographic coverage overview

### 💬 Internal Messaging

- Communication between teachers, coaches, recruiters, and managers
- Context-based conversations around talents
- Dashboard-integrated messaging system

### 🔐 Security Features

- Secure login system
- Password hashing
- PHP sessions
- Role-based access control
- CSRF protection
- Private media handling
- Protected uploads
- PDO prepared statements

---

## 🧠 AI Analysis Workflow

```text
Teacher uploads video
        ↓
Platform stores the video securely
        ↓
Key frames / video data are prepared for analysis
        ↓
AI evaluates the athlete performance
        ↓
Scores and recommendations are saved
        ↓
Students, teachers, coaches, and recruiters view results
