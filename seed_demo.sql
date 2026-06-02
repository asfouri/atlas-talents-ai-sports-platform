-- Optional Atlas Talents demo seed
-- Import only in local/demo environments after schema.sql.

USE atlas_talents;

INSERT INTO users (name, email, password, role, club, ville, student_id) VALUES
('Prof. Hassan Alami', 'teacher@demo.com', '$2y$12$p45/3AGsRj98Rnyd2YWSu.naMngg88BHxFlmHenboz5iPkl3qbYPO', 'teacher', NULL, 'Casablanca', NULL),
('Karim Recruteur', 'recruiter@demo.com', '$2y$12$p45/3AGsRj98Rnyd2YWSu.naMngg88BHxFlmHenboz5iPkl3qbYPO', 'recruiter', 'Raja Club Athletic', 'Casablanca', NULL),
('Coach Ahmed', 'coach@demo.com', '$2y$12$p45/3AGsRj98Rnyd2YWSu.naMngg88BHxFlmHenboz5iPkl3qbYPO', 'coach', 'AS FAR Rabat', 'Rabat', NULL),
('Meryem Manager', 'manager@demo.com', '$2y$12$p45/3AGsRj98Rnyd2YWSu.naMngg88BHxFlmHenboz5iPkl3qbYPO', 'manager', 'Atlas Scout Club', 'Casablanca', NULL);

INSERT INTO students (teacher_id, name, age, ville, sport, school) VALUES
(1, 'Youssef El Amrani', 14, 'Casablanca', 'Athletisme', 'College Al Massira'),
(1, 'Fatima Zahra Bennani', 15, 'Rabat', 'Gymnastique', 'College Mohammed V'),
(1, 'Amine Ouali', 13, 'Marrakech', 'Football', 'College Ibn Khaldoun'),
(1, 'Layla El Fassi', 14, 'Fes', 'Natation', 'Lycee Al Qods'),
(1, 'Khalid Mansouri', 16, 'Marrakech', 'Football', 'Lycee Ibn Sina'),
(1, 'Sara Idrissi', 14, 'Fes', 'Natation', 'College Al Fath');

INSERT INTO users (name, email, password, role, club, ville, student_id) VALUES
('Youssef El Amrani', 'student@demo.com', '$2y$12$p45/3AGsRj98Rnyd2YWSu.naMngg88BHxFlmHenboz5iPkl3qbYPO', 'student', 'College Al Massira', 'Casablanca', 1);

INSERT INTO videos (student_id, teacher_id, perf_type, vitesse, coordination, endurance, `force`, souplesse, score_global, ai_status, analyzed_at) VALUES
(1, 1, 'Sprint', 92, 88, 85, 79, 82, 87, 'done', '2024-03-27 10:00:00'),
(2, 1, 'Gym artistique', 85, 94, 88, 81, 91, 91, 'done', '2024-03-22 14:00:00'),
(3, 1, 'Dribble', 76, 70, 78, 72, 69, 73, 'done', '2024-03-18 09:00:00'),
(4, 1, 'Nage libre', 87, 83, 92, 78, 88, 86, 'done', '2024-03-24 16:00:00'),
(5, 1, 'Match football', 89, 86, 90, 84, 80, 86, 'done', '2024-03-24 08:00:00'),
(6, 1, 'Nage papillon', 86, 82, 90, 76, 85, 84, 'done', '2024-03-22 11:00:00');

INSERT INTO coach_students (coach_id, student_id, start_date, notes) VALUES
(3, 1, '2024-01-08', 'Suivi vitesse et technique sprint'),
(3, 2, '2024-01-15', 'Suivi coordination et gainage'),
(3, 5, '2024-02-01', 'Suivi profil football competition');

INSERT INTO messages (sender_id, recipient_id, student_id, body, is_read, created_at) VALUES
(2, 1, 1, 'Bonjour professeur, le profil de Youssef nous interesse pour un test sprint.', 1, '2026-04-12 09:30:00'),
(1, 2, 1, 'Parfait, je peux partager une nouvelle video et ses derniers temps de passage.', 1, '2026-04-12 10:05:00'),
(5, 1, 2, 'Fatima Zahra a un profil tres propre. Pouvons-nous organiser une evaluation club ?', 1, '2026-04-13 11:10:00'),
(1, 5, 2, 'Oui, je vous envoie ses disponibilites et son dernier resume IA.', 1, '2026-04-13 11:26:00'),
(3, 6, 1, 'On garde la seance de travail vitesse jeudi a 15h.', 1, '2026-04-13 17:00:00');
