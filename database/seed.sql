USE careerpath_db;
INSERT INTO roles (name) VALUES ('registered_user'),('content_editor'),('moderator'),('administrator') ON DUPLICATE KEY UPDATE name=VALUES(name);
INSERT INTO career_categories (name, slug, description) VALUES
('Technology and Computing','technology-computing','Build and maintain digital products, platforms, and systems.'),
('Business and Entrepreneurship','business-entrepreneurship','Create value through strategy, operations, leadership, and innovation.'),
('Creative and Digital','creative-digital','Combine creative thinking with digital tools and communication.');
INSERT INTO careers (category_id,title,slug,summary,overview,level,remote_friendly) VALUES
(1,'Software Developer','software-developer','Build, test, and improve software products.','Software developers translate problems into reliable, maintainable digital solutions.','beginner',1),
(1,'Data Analyst','data-analyst','Turn data into useful insights for decisions.','Data analysts clean, explore, visualize, and communicate information to support organizations.','beginner',1),
(3,'UX Designer','ux-designer','Design clear and useful experiences for people.','UX designers research user needs and shape accessible, intuitive product experiences.','beginner',1),
(2,'Project Manager','project-manager','Help teams deliver valuable work effectively.','Project managers align goals, people, scope, risks, and communication across initiatives.','intermediate',1);
INSERT INTO skills (name) VALUES ('Communication'),('Problem solving'),('HTML and CSS'),('JavaScript'),('SQL'),('User research'),('Project planning') ON DUPLICATE KEY UPDATE name=VALUES(name);
INSERT INTO learning_paths (title,slug,description,level,published) VALUES
('Zero to Web Developer','zero-to-web-developer','Learn web fundamentals through practical projects and a portfolio.','beginner',1),
('Getting Started with Python','getting-started-with-python','Build programming confidence with small, useful Python projects.','beginner',1),
('Digital Marketing Professional Roadmap','digital-marketing-roadmap','Develop a practical foundation in content, search, and measurement.','beginner',1);
INSERT INTO learning_modules (learning_path_id,title,position)
SELECT id,'Web foundations',1 FROM learning_paths WHERE slug='zero-to-web-developer'
ON DUPLICATE KEY UPDATE title=VALUES(title);
INSERT INTO learning_modules (learning_path_id,title,position)
SELECT id,'Building for the browser',2 FROM learning_paths WHERE slug='zero-to-web-developer'
ON DUPLICATE KEY UPDATE title=VALUES(title);
INSERT INTO lessons (module_id,title,content,position)
SELECT lm.id,'How the web works','Understand browsers, servers, requests, responses, and the role of HTML.',1 FROM learning_modules lm INNER JOIN learning_paths lp ON lp.id=lm.learning_path_id WHERE lp.slug='zero-to-web-developer' AND lm.position=1
ON DUPLICATE KEY UPDATE title=VALUES(title),content=VALUES(content);
INSERT INTO lessons (module_id,title,content,position)
SELECT lm.id,'Writing semantic HTML','Create clear page structures with headings, landmarks, links, and accessible forms.',2 FROM learning_modules lm INNER JOIN learning_paths lp ON lp.id=lm.learning_path_id WHERE lp.slug='zero-to-web-developer' AND lm.position=1
ON DUPLICATE KEY UPDATE title=VALUES(title),content=VALUES(content);
INSERT INTO lessons (module_id,title,content,position)
SELECT lm.id,'Styling with CSS','Use selectors, layout, spacing, and responsive rules to create a usable interface.',1 FROM learning_modules lm INNER JOIN learning_paths lp ON lp.id=lm.learning_path_id WHERE lp.slug='zero-to-web-developer' AND lm.position=2
ON DUPLICATE KEY UPDATE title=VALUES(title),content=VALUES(content);
INSERT INTO lessons (module_id,title,content,position)
SELECT lm.id,'Adding JavaScript interactions','Use events and DOM updates to make a small interface respond to user actions.',2 FROM learning_modules lm INNER JOIN learning_paths lp ON lp.id=lm.learning_path_id WHERE lp.slug='zero-to-web-developer' AND lm.position=2
ON DUPLICATE KEY UPDATE title=VALUES(title),content=VALUES(content);
INSERT INTO articles (title,slug,excerpt,body,status,published_at) VALUES
('How to choose a career direction','choose-career-direction','A practical framework for exploring options without needing a perfect answer.','Start with curiosity, evidence, and small experiments. Compare responsibilities, skills, and learning opportunities before committing.','published',NOW()),
('Building a portfolio through practical projects','build-portfolio-projects','Use projects to demonstrate how you think and what you can do.','A strong project explains the problem, your decisions, the result, and what you learned.','published',NOW());
INSERT INTO resources (title,description,url,category,level,reviewed_at,published) VALUES
('MDN Web Docs','Reference documentation for web standards and browser APIs.','https://developer.mozilla.org/','Documentation','all',CURDATE(),1),
('PHP Manual','Official reference for the PHP language and standard library.','https://www.php.net/docs.php','Documentation','all',CURDATE(),1),
('MySQL Documentation','Official documentation for MySQL database concepts and SQL.','https://dev.mysql.com/doc/','Documentation','all',CURDATE(),1);
INSERT INTO challenges (title,description,action_text,duration_days,published) VALUES
('One small career experiment','Choose one career direction and test it with a focused, low-risk activity.','Spend 30 minutes reviewing a real job description and write down three skills to investigate.',7,1),
('Build a learning habit','Create a repeatable learning routine that fits your current schedule.','Complete one lesson and record one practical takeaway on three different days.',7,1);
INSERT INTO assessments (title,description,published)
VALUES ('Career direction starter assessment','Answer a few questions about the kind of work you want to explore. Recommendations are exploratory and rules-based.',1);
INSERT INTO assessment_questions (assessment_id,question,position)
SELECT id,'Which activity sounds most satisfying?',1 FROM assessments WHERE title='Career direction starter assessment'
ON DUPLICATE KEY UPDATE question=VALUES(question);
INSERT INTO assessment_questions (assessment_id,question,position)
SELECT id,'What kind of problems would you like to solve?',2 FROM assessments WHERE title='Career direction starter assessment'
ON DUPLICATE KEY UPDATE question=VALUES(question);
INSERT INTO assessment_questions (assessment_id,question,position)
SELECT id,'Which working output appeals to you most?',3 FROM assessments WHERE title='Career direction starter assessment'
ON DUPLICATE KEY UPDATE question=VALUES(question);
INSERT INTO assessment_options (question_id,label,career_id,weight)
SELECT q.id,'Building a useful digital product',c.id,1 FROM assessment_questions q INNER JOIN careers c ON c.slug='software-developer' WHERE q.position=1 AND q.assessment_id=(SELECT id FROM assessments WHERE title='Career direction starter assessment')
UNION ALL SELECT q.id,'Finding patterns in information',c.id,1 FROM assessment_questions q INNER JOIN careers c ON c.slug='data-analyst' WHERE q.position=1 AND q.assessment_id=(SELECT id FROM assessments WHERE title='Career direction starter assessment')
UNION ALL SELECT q.id,'Improving how people use a service',c.id,1 FROM assessment_questions q INNER JOIN careers c ON c.slug='ux-designer' WHERE q.position=1 AND q.assessment_id=(SELECT id FROM assessments WHERE title='Career direction starter assessment')
UNION ALL SELECT q.id,'Coordinating people and priorities',c.id,1 FROM assessment_questions q INNER JOIN careers c ON c.slug='project-manager' WHERE q.position=1 AND q.assessment_id=(SELECT id FROM assessments WHERE title='Career direction starter assessment');
INSERT INTO assessment_options (question_id,label,career_id,weight)
SELECT q.id,'Technical and logical challenges',c.id,1 FROM assessment_questions q INNER JOIN careers c ON c.slug='software-developer' WHERE q.position=2 AND q.assessment_id=(SELECT id FROM assessments WHERE title='Career direction starter assessment')
UNION ALL SELECT q.id,'Evidence-based business questions',c.id,1 FROM assessment_questions q INNER JOIN careers c ON c.slug='data-analyst' WHERE q.position=2 AND q.assessment_id=(SELECT id FROM assessments WHERE title='Career direction starter assessment')
UNION ALL SELECT q.id,'Human needs and usability',c.id,1 FROM assessment_questions q INNER JOIN careers c ON c.slug='ux-designer' WHERE q.position=2 AND q.assessment_id=(SELECT id FROM assessments WHERE title='Career direction starter assessment')
UNION ALL SELECT q.id,'Goals, risks, and delivery plans',c.id,1 FROM assessment_questions q INNER JOIN careers c ON c.slug='project-manager' WHERE q.position=2 AND q.assessment_id=(SELECT id FROM assessments WHERE title='Career direction starter assessment');
INSERT INTO assessment_options (question_id,label,career_id,weight)
SELECT q.id,'A working application or automation',c.id,1 FROM assessment_questions q INNER JOIN careers c ON c.slug='software-developer' WHERE q.position=3 AND q.assessment_id=(SELECT id FROM assessments WHERE title='Career direction starter assessment')
UNION ALL SELECT q.id,'A clear report or dashboard',c.id,1 FROM assessment_questions q INNER JOIN careers c ON c.slug='data-analyst' WHERE q.position=3 AND q.assessment_id=(SELECT id FROM assessments WHERE title='Career direction starter assessment')
UNION ALL SELECT q.id,'A tested prototype or user flow',c.id,1 FROM assessment_questions q INNER JOIN careers c ON c.slug='ux-designer' WHERE q.position=3 AND q.assessment_id=(SELECT id FROM assessments WHERE title='Career direction starter assessment')
UNION ALL SELECT q.id,'A plan that helps a team deliver',c.id,1 FROM assessment_questions q INNER JOIN careers c ON c.slug='project-manager' WHERE q.position=3 AND q.assessment_id=(SELECT id FROM assessments WHERE title='Career direction starter assessment');
INSERT INTO site_settings (setting_key,setting_value) VALUES ('site_tagline','Discover what you can become.') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);
