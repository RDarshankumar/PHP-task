# Online Learning Management System

This is a simple PHP-based Learning Management System (LMS) that supports three user roles: **Student**, **Instructor**, and **Admin**. The system allows course creation, lesson and quiz management, student enrollment, quiz attempts, and course approval.

---

## 📁 Project Structure

```
config/
  database_schema.sql         # MySQL database schema
  db_connection.php           # Database connection script

public/
  index.php                   # Login page
  register.php                # Registration page
  student.php                 # Student dashboard
  instructor.php              # Instructor dashboard
  admin.php                   # Admin dashboard
  view_course.php             # Course view & progress for students
  take_quiz.php               # Quiz attempt page for students
  submit_quiz.php             # Quiz submission handler
  add_question.php            # Add/view quiz questions (instructor)
```

---

## 🗄️ Database Schema

See [`config/database_schema.sql`](config/database_schema.sql) for full details.

- **users**: Stores user info (name, email, password, role)
- **courses**: Courses with title, description, instructor, price, status
- **lessons**: Lessons for each course
- **lesson_progress**: Tracks which lessons a student has completed
- **enrollments**: Tracks which students are enrolled in which courses
- **quizzes**: Quizzes for each course
- **questions**: Questions for each quiz (with options and correct answer)
- **submissions**: Quiz submissions by students (with score)
- **certificates**: Certificates issued to students for course completion

---

## 👤 User Roles & Features

### 1. Student

- **Register/Login**: via [`register.php`](public/register.php) and [`index.php`](public/index.php)
- **Dashboard**: [`student.php`](public/student.php) shows available courses
- **Enroll in Courses**: Auto-enrolls on viewing a course ([`view_course.php`](public/view_course.php))
- **View Lessons**: See lessons, mark as complete, track progress
- **Attempt Quizzes**: Take quizzes, see scores
- **Progress Tracking**: Visual progress bar for lessons completed

### 2. Instructor

- **Dashboard**: [`instructor.php`](public/instructor.php)
- **Create/Edit/Delete Courses**
- **Add Lessons**: Add lessons to courses
- **Add Quizzes**: Create quizzes for courses
- **Add Questions**: [`add_question.php`](public/add_question.php) for quiz questions
- **View Student Submissions**: See who attempted quizzes and their scores

### 3. Admin

- **Dashboard**: [`admin.php`](public/admin.php)
- **Approve/Reject Courses**: Publish or archive courses submitted by instructors

---

## 📝 Main Pages

- **Login**: [`index.php`](public/index.php)
- **Register**: [`register.php`](public/register.php)
- **Student Dashboard**: [`student.php`](public/student.php)
- **Instructor Dashboard**: [`instructor.php`](public/instructor.php)
- **Admin Dashboard**: [`admin.php`](public/admin.php)
- **Course View (Student)**: [`view_course.php`](public/view_course.php)
- **Quiz Attempt**: [`take_quiz.php`](public/take_quiz.php)
- **Quiz Submission Handler**: [`submit_quiz.php`](public/submit_quiz.php)
- **Add Questions (Instructor)**: [`add_question.php`](public/add_question.php)

---

## ⚙️ How It Works

- **Authentication**: Session-based login, with user info also stored in `localStorage` for UI
- **Course Approval**: Instructors create courses (pending by default), admin must approve to publish
- **Enrollment**: Students are auto-enrolled when viewing a course
- **Lesson Progress**: Students mark lessons as complete; progress is tracked and shown
- **Quiz System**: Instructors add quizzes and questions; students can attempt each quiz once
- **Submissions**: Quiz scores are stored and visible to instructors

---

## 🚀 Getting Started

1. **Clone the repo and set up in your XAMPP/htdocs directory**
2. **Import the database**
   - Use phpMyAdmin or MySQL CLI to run [`config/database_schema.sql`](config/database_schema.sql)
3. **Configure DB Connection**
   - Edit [`config/db_connection.php`](config/db_connection.php) if your MySQL credentials differ
4. **Access the app**
   - Open `http://localhost/Task/public/index.php` in your browser

---

## 🛡️ Security Notes

- Passwords are hashed using `password_hash`
- SQL queries use prepared statements to prevent SQL injection
- Session management is used for authentication

---

## 🎨 UI

- Uses [Tailwind CSS](https://tailwindcss.com/) for styling
- Uses [SweetAlert2](https://sweetalert2.github.io/) for alerts

---

## 📚 File Reference

- [`config/database_schema.sql`](config/database_schema.sql): Full DB schema
- [`config/db_connection.php`](config/db_connection.php): MySQL connection
- [`public/index.php`](public/index.php): Login logic and UI
- [`public/register.php`](public/register.php): Registration logic and UI
- [`public/student.php`](public/student.php): Student dashboard
- [`public/instructor.php`](public/instructor.php): Instructor dashboard and course/quiz/lesson management
- [`public/admin.php`](public/admin.php): Admin dashboard for course approval
- [`public/view_course.php`](public/view_course.php): Student course view, progress, and quizzes
- [`public/take_quiz.php`](public/take_quiz.php): Quiz attempt page
- [`public/submit_quiz.php`](public/submit_quiz.php): Quiz submission handler
- [`public/add_question.php`](public/add_question.php): Add/view quiz questions and see submissions

---

## 📝 Notes
- admin id = admin@email.com password= 123
- No email verification or password reset is implemented
- No file uploads (e.g., for lesson materials)
- No certificate download logic, but certificates table exists for future use

---

## 📞 Support

For any issues, please contact the project maintainer.

---


**Enjoy learning and teaching!**
