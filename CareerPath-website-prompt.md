1\. Project Overview and Vision 

Act as a senior full-stack software architect, UI/UX designer, PHP developer, MySQL database engineer, cybersecurity specialist, and educational content strategist.

Your task is to plan, design, and develop a complete, production-ready web application called CareerPath, a comprehensive career counselling, professional development, and learning platform.

The platform should help students, graduates, job seekers, working professionals, entrepreneurs, freelancers, career changers, and anyone interested in personal or professional development make informed career decisions and remain competitive in the rapidly evolving digital and AI-powered world.

The platform must provide practical career advice, career exploration, educational guides, step-by-step tutorials, professional development resources, learning roadmaps, and opportunities for continuous improvement.

It should go beyond being an ordinary career information website. Build an interactive career development ecosystem where users can discover career opportunities, understand required skills, identify knowledge gaps, follow structured learning paths, track their progress, and prepare for the future of work.

Core philosophy:

* Help people discover what they can become.  
* Help people understand what they need to learn.  
* Help people build practical and marketable skills.  
* Help people adapt to AI-driven changes in the workplace.  
* Help people turn knowledge into real opportunities.  
* Promote lifelong learning, innovation, entrepreneurship, and professional growth.

## **2\. Technology Stack and Development Restrictions**

Use the following technologies exclusively for the core application.

| Layer | Required technology |
| ----- | ----- |
| Frontend | Semantic HTML5 |
| Styling | Native CSS3 |
| Client-side functionality | Vanilla JavaScript (ES6+) |
| Backend | Native PHP 8.2+ |
| Database | MySQL 8.0+ or compatible MariaDB |
| Database communication | PHP PDO |
| Local development | XAMPP |
| Web server | Apache |
| Deployment | Standard PHP/MySQL hosting or VPS |

Strict development restrictions:

1. Do not use Laravel, Symfony, CodeIgniter, WordPress, or any other PHP framework.  
2. Do not use React, Vue, Angular, Next.js, or other frontend frameworks.  
3. Do not use Bootstrap, TailwindCSS, Bulma, or other CSS frameworks.  
4. Do not use Node.js as an application runtime or require npm to run the website.  
5. Do not require Composer or third-party PHP packages for core application functionality.  
6. Do not depend on external services for essential website functionality.  
7. Build reusable components and functionality using native PHP, HTML, CSS, and JavaScript.  
8. CDN libraries may only be considered for optional enhancements, not essential functionality. Provide local or native fallbacks wherever practical.

The application must work on a standard XAMPP installation and remain portable to ordinary PHP/MySQL hosting environments.

## **3\. Target Users and User Categories**

Design the platform to serve multiple user groups.

* Secondary school and university students exploring career choices.  
* Recent graduates preparing for employment.  
* Job seekers searching for career opportunities.  
* Software developers and other technology professionals.  
* Entrepreneurs and aspiring business owners.  
* Freelancers and digital creators.  
* Professionals seeking promotions or career transitions.  
* People interested in artificial intelligence and emerging technologies.  
* Individuals seeking practical skills for remote work and the digital economy.

Create a system that allows users to explore resources relevant to their career stage, experience level, interests, and learning goals.

## **4\. Core Platform Modules and Functionalities**

Develop the following modules as interconnected parts of one application.

### **Module A: Career Discovery and Exploration**

Create a comprehensive career directory covering multiple professional industries.

Suggested career categories include:

Technology and Computing

* Software engineering.  
* Frontend and backend development.  
* Full-stack development.  
* Mobile application development.  
* Artificial intelligence and machine learning.  
* Data science and data analytics.  
* Cybersecurity.  
* Cloud computing and DevOps.  
* Database administration.  
* UI/UX design.  
* Game development.  
* Blockchain development.  
* IT support and systems administration.

Business and Entrepreneurship

* Business administration.  
* Digital entrepreneurship.  
* Project management.  
* Business analysis.  
* Digital marketing.  
* Sales and business development.  
* Financial technology.  
* E-commerce.  
* Human resource management.  
* Product management.

Creative and Digital Careers

* Graphic design.  
* Video editing.  
* Content creation.  
* Technical writing.  
* Blogging.  
* SEO specialist.  
* Social media management.  
* Animation.  
* Photography.  
* Digital advertising.

Other Professional Fields

* Healthcare.  
* Engineering.  
* Education.  
* Agriculture and agritech.  
* Renewable energy.  
* Law.  
* Finance and accounting.  
* Logistics and supply chain.  
* Hospitality and tourism.  
* Public administration.  
* Skilled trades.

Every career profile should contain:

* Career title and overview.  
* Industry and specialization.  
* What professionals in that field actually do.  
* Essential responsibilities.  
* Required educational qualifications.  
* Beginner, intermediate, and advanced skills.  
* Recommended tools and technologies.  
* Relevant certifications and qualifications.  
* Suggested learning resources.  
* Practical projects and portfolio ideas.  
* Entry-level opportunities.  
* Career progression and advancement opportunities.  
* Freelancing and entrepreneurship opportunities where applicable.  
* Remote work possibilities.  
* How AI is influencing the profession.  
* Potential future developments and risks.  
* Suggested next steps for entering the profession.

Allow users to filter careers by industry, skill level, educational requirements, work arrangement, and career interests.

### **Module B: AI Era Career Guidance**

This is one of the most important sections of the application.

Develop a dedicated Future of Work and AI Career Center that helps users understand how artificial intelligence is transforming industries and professional responsibilities.

Cover subjects such as:

* Understanding artificial intelligence.  
* Generative AI and large language models.  
* AI tools for everyday productivity.  
* AI-assisted software development.  
* AI-powered digital marketing.  
* AI in business administration.  
* AI in education.  
* AI in healthcare.  
* AI in financial services.  
* AI automation and workflow optimization.  
* AI agents and autonomous systems.  
* Prompt engineering.  
* AI product development.  
* Data-driven decision-making.  
* Responsible AI and digital ethics.  
* Human skills that remain valuable in AI-assisted workplaces.

Include specialized learning paths such as:

1. AI literacy for complete beginners.  
2. Becoming an AI-powered software developer.  
3. Becoming an AI automation specialist.  
4. Learning data analytics with AI tools.  
5. Building AI-powered digital businesses.  
6. Becoming a prompt engineering specialist.  
7. AI-assisted content creation.  
8. AI tools for freelancers and entrepreneurs.  
9. Building and deploying AI-powered applications.  
10. Understanding AI agents and intelligent workflows.

Every guide should focus on practical application rather than just theoretical explanations.

Avoid claims that AI will definitely eliminate or guarantee demand for a particular profession. Discuss uncertainty, changing responsibilities, and the importance of continuously updating skills.

### **Module C: Career Counselling and Self-Assessment**

Build an interactive career assessment system.

Users should be able to complete assessments covering:

* Personal interests.  
* Professional strengths.  
* Existing skills.  
* Preferred working environment.  
* Educational background.  
* Career ambitions.  
* Preferred industries.  
* Learning preferences.  
* Work-life priorities.  
* Entrepreneurial interests.

Based on their answers, generate a career discovery report containing:

* Suggested career categories.  
* Matching career options.  
* Relevant transferable skills.  
* Skills they may need to develop.  
* Recommended learning paths.  
* Suggested starter projects.  
* Practical next steps.

Use a transparent, rules-based recommendation engine for the initial version.

Do not claim that a quiz scientifically determines someone's ideal career. Explain that its recommendations are exploratory suggestions, not a professional psychological or psychometric assessment.

Allow registered users to save their assessment results and retake assessments.

### **Module D: Learning Paths and Skill Development**

Create structured learning roadmaps for different professional careers.

Each learning path should contain:

* Learning objective.  
* Required prerequisites.  
* Beginner stage.  
* Intermediate stage.  
* Advanced stage.  
* Relevant tools and technologies.  
* Learning materials.  
* Practical assignments.  
* Mini-projects.  
* Portfolio-building activities.  
* Self-assessment questions.  
* Recommended next steps.

Example learning paths:

* Zero to Professional Web Developer.  
* Beginner to Full-Stack Software Engineer.  
* Getting Started with Python.  
* Frontend Development with HTML, CSS, and JavaScript.  
* Backend Development with PHP and MySQL.  
* Beginner to Data Analyst.  
* Getting Started with Cybersecurity.  
* AI Literacy to AI Application Developer.  
* Digital Marketing Professional Roadmap.  
* Starting a Freelance Career.  
* Building and Managing a Digital Business.

Users should be able to enroll in learning paths, mark lessons as completed, save their progress, and resume learning later.

### **Module E: Career Guides and Tutorials**

Create a publishing system for high-quality educational content.

Content types should include:

* Career guides.  
* Step-by-step tutorials.  
* Technical articles.  
* Beginner-friendly explanations.  
* Professional development articles.  
* Industry insights.  
* Practical project guides.  
* Interview preparation materials.  
* Certification guides.  
* Career transition guides.  
* AI and technology updates.

Each article should support:

* Title and subtitle.  
* Featured image.  
* Category and tags.  
* Author information.  
* Publication date.  
* Reading time.  
* Table of contents.  
* Main content.  
* Related articles.  
* Share functionality.  
* Bookmark functionality.  
* Comments, subject to moderation.  
* SEO title and meta description.

Create a rich content management experience using a native HTML editor or a carefully secured, lightweight editing interface. Store content safely and render it securely.

### **Module F: Professional Development and Job Readiness**

Build a career preparation center containing:

CV and Resume Development

* CV writing guides.  
* Resume structure examples.  
* Professional summary examples.  
* Skills presentation advice.  
* Experience descriptions.  
* Common CV mistakes.  
* Role-specific CV guidance.

Interview Preparation

* Common interview questions.  
* Technical interview guidance.  
* Behavioural interview questions.  
* STAR interview response techniques.  
* Mock interview preparation.  
* Interview etiquette.  
* Remote interview preparation.

Portfolio and Professional Branding

* Building a professional portfolio.  
* GitHub profile development.  
* LinkedIn profile improvement.  
* Personal branding.  
* Professional networking.  
* Technical blogging.  
* Project documentation.

Employment and Career Growth

* Job search strategies.  
* Internship preparation.  
* Entry-level career opportunities.  
* Remote work preparation.  
* Salary negotiation education.  
* Workplace communication.  
* Leadership development.  
* Career advancement.

### **Module G: Freelancing and Entrepreneurship**

Create a dedicated section for individuals interested in earning independently or building businesses.

Cover:

* Getting started with freelancing.  
* Choosing profitable service categories.  
* Creating professional service packages.  
* Building an online portfolio.  
* Client communication.  
* Proposal writing.  
* Pricing digital services.  
* Managing projects and deadlines.  
* Building client relationships.  
* Digital product development.  
* Starting an online business.  
* Monetizing technical and creative skills.  
* Using AI tools responsibly to improve productivity.

Include practical business planning worksheets, templates, and guides.

### **Module H: Career Tools and Interactive Utilities**

Develop useful native JavaScript and PHP tools that make the platform more interactive.

Include:

1. Career interest assessment.  
2. Skills gap analyzer.  
3. Career roadmap generator.  
4. Learning progress tracker.  
5. Daily learning planner.  
6. Goal-setting tool.  
7. SMART career goal builder.  
8. Study time calculator.  
9. Interview question practice tool.  
10. CV checklist.  
11. Portfolio checklist.  
12. Freelance readiness assessment.  
13. Professional skills self-assessment.  
14. Career comparison tool.  
15. Learning resource organizer.  
16. Personal development journal.  
17. Certification planning tool.  
18. Career transition planner.  
19. Project idea generator using curated templates.  
20. AI-era skills checklist.

Each tool should have a working interface, clear instructions, meaningful outputs, and appropriate validation.

Where information needs to persist, store it in the MySQL database for authenticated users.

### **Module I: Resource Library**

Build a searchable resource center containing:

* Free learning platforms.  
* Recommended books.  
* Online courses.  
* Technical documentation.  
* Professional certifications.  
* Career development websites.  
* Developer tools.  
* AI tools and platforms.  
* Communities and professional networks.  
* Scholarship and internship resources.  
* Open-source learning materials.

Each resource should include a title, description, category, resource type, official URL, target skill level, and date last reviewed.

Allow administrators to add, update, approve, and remove resources.

Do not fabricate resource links, certifications, or course details. Administrators should be able to maintain and verify published resources.

### **Module J: Community and User Engagement**

Develop a manageable community section with:

* User discussion topics.  
* Questions and answers.  
* Career experience sharing.  
* Learning challenges.  
* Project showcase submissions.  
* User comments.  
* Replies.  
* Content reporting.  
* Moderation tools.

Implement permissions and moderation to prevent spam, abusive content, and unsafe links.

Community functionality should be modular so it can be expanded without redesigning the entire system.

## **5\. User Accounts and Role Management**

Implement a secure authentication and authorization system.

User roles:

| Role | Permissions |
| ----- | ----- |
| Guest | Browse public content and resources |
| Registered user | Save careers, take assessments, enroll in learning paths, track progress |
| Content editor | Create and edit assigned content |
| Moderator | Moderate comments, discussions, and reports |
| Administrator | Manage the entire website, content, users, settings, and system data |

User account functionalities:

* Registration.  
* Login and logout.  
* Secure password hashing.  
* Email verification, where hosting supports it.  
* Password reset.  
* Profile editing.  
* Profile image upload.  
* Saved career profiles.  
* Bookmarked articles.  
* Learning path enrollment.  
* Learning progress tracking.  
* Assessment history.  
* Personal dashboard.  
* Account deactivation or deletion.

Use secure PHP sessions and role-based access control. Never rely on frontend-only permissions.

## **6\. Admin Dashboard and Content Management System**

Build a professional administration dashboard that acts as the central management hub.

The dashboard should provide:

Overview

* Total registered users.  
* New registrations.  
* Published articles.  
* Draft articles.  
* Available careers.  
* Learning paths.  
* Total resources.  
* Pending comments and reports.  
* Recent platform activity.

Content Management

* Create and update career profiles.  
* Manage career categories.  
* Publish and edit articles.  
* Create tutorials.  
* Manage learning paths and lessons.  
* Manage resource library entries.  
* Upload and manage media.  
* Configure article tags.  
* Schedule content publication if practical.

User Management

* View users.  
* Search and filter accounts.  
* Manage roles.  
* Suspend and reactivate accounts.  
* Review reported activity.  
* View user engagement data.

System Management

* Manage website settings.  
* Configure site identity.  
* Manage navigation links.  
* Update social media URLs.  
* Configure homepage sections.  
* Manage SEO defaults.  
* View security and audit logs.  
* Manage contact messages.

Separate administrative functions into clean, maintainable PHP modules.

## **7\. UI/UX and Visual Design Requirements**

Create a premium, modern, professional, engaging, and highly accessible educational platform.

The design must communicate trust, knowledge, opportunity, clarity, and professional growth.

Design direction:

* Modern educational technology aesthetic.  
* Professional career coaching visual language.  
* Clear typography and visual hierarchy.  
* Spacious layouts.  
* Carefully balanced colours.  
* Consistent buttons, forms, cards, and navigation.  
* Attractive career and learning illustrations or imagery.  
* Subtle transitions and micro-interactions.  
* Consistent iconography.  
* Well-designed charts and progress indicators where useful.

Suggested visual identity:

* Primary colour: Deep navy or professional indigo.  
* Secondary colour: Modern blue.  
* Accent: Teal or warm amber.  
* Background: Clean white or soft neutral.  
* Text: High-contrast dark slate.

Support both light and dark themes.

### **Required Pages**

Develop the following public-facing pages:

1. Home.  
2. About Us.  
3. Career Discovery.  
4. Career Categories.  
5. Individual Career Profile.  
6. AI Era Career Center.  
7. Learning Paths.  
8. Individual Learning Path.  
9. Tutorials and Guides.  
10. Article Details.  
11. Professional Development.  
12. Freelancing and Entrepreneurship.  
13. Career Tools.  
14. Resource Library.  
15. Community.  
16. Contact.  
17. Frequently Asked Questions.  
18. Privacy Policy.  
19. Terms and Conditions.  
20. Login.  
21. Registration.  
22. Forgot Password.  
23. User Dashboard.

Also build separate administrative pages and dashboards.

### **Homepage Structure**

The homepage should include:

1. Professional navigation bar.  
2. Hero section with a compelling career development message.  
3. Prominent career exploration search.  
4. Career categories.  
5. Featured career opportunities.  
6. AI-era career guidance section.  
7. Popular learning paths.  
8. Latest guides and tutorials.  
9. Career assessment call-to-action.  
10. Professional development resources.  
11. Freelancing and entrepreneurship highlights.  
12. Featured interactive career tools.  
13. Testimonials, only where genuine.  
14. Newsletter subscription section.  
15. Footer with navigation, contact details, and social links.

Include compelling calls to action such as:

* Explore Careers.  
* Discover Your Potential.  
* Build Your Skills.  
* Start Your Learning Journey.  
* Prepare for the AI Era.  
* Take a Career Assessment.

## **8\. Database Architecture and MySQL Design**

Design a normalized, scalable, and maintainable relational database.

Use MySQL with appropriate primary keys, foreign keys, indexes, unique constraints, and timestamps.

Create SQL schema files for the following tables, adjusting the final design where necessary:

| Table | Purpose |
| ----- | ----- |
| users | User accounts and profile data |
| roles | Application roles |
| user\_roles | User role assignments |
| careers | Career profiles |
| career\_categories | Career classifications |
| career\_skills | Skills associated with careers |
| skills | Reusable skill records |
| articles | Guides, tutorials, and blog posts |
| article\_categories | Content categories |
| article\_tags | Content tagging |
| tags | Reusable tags |
| learning\_paths | Structured learning programs |
| learning\_modules | Learning path modules |
| lessons | Individual learning lessons |
| enrollments | User learning path registrations |
| lesson\_progress | Completed lessons and progress |
| assessments | Career assessment definitions |
| assessment\_questions | Assessment questions |
| assessment\_options | Assessment answer options |
| assessment\_results | User assessment results |
| saved\_careers | Bookmarked career profiles |
| bookmarks | Saved content and resources |
| resources | External learning resources |
| comments | Article and community comments |
| discussions | Community discussions |
| discussion\_replies | Discussion responses |
| contact\_messages | Contact form submissions |
| newsletter\_subscribers | Email subscriptions |
| notifications | User notifications |
| user\_goals | Career and learning goals |
| user\_notes | Personal development notes |
| media | Uploaded media metadata |
| site\_settings | Configurable website settings |
| audit\_logs | Important administrative activity |

Implement appropriate relationships and cascading rules.

Avoid unnecessary duplication of user or content data.

Use transactions for multi-step database operations.

Create database seed data for demonstration purposes, including sample career profiles, learning paths, tutorials, resources, and career categories.

Provide a clear SQL import file that can be imported through phpMyAdmin.

## **9\. Application Architecture and Folder Structure**

Build a clean modular architecture without frameworks.

Use a structure similar to the following:

```
careerpath/
│
├── app/
│   ├── config/
│   │   ├── config.example.php
│   │   ├── database.php
│   │   └── app.php
│   │
│   ├── core/
│   │   ├── Database.php
│   │   ├── Router.php
│   │   ├── Auth.php
│   │   ├── Validator.php
│   │   ├── CSRF.php
│   │   ├── Session.php
│   │   ├── Security.php
│   │   └── Helpers.php
│   │
│   ├── middleware/
│   │   ├── auth.php
│   │   ├── guest.php
│   │   └── admin.php
│   │
│   ├── models/
│   ├── services/
│   ├── repositories/
│   └── views/
│       ├── layouts/
│       ├── components/
│       └── partials/
│
├── public/
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   ├── images/
│   │   └── fonts/
│   │
│   ├── uploads/
│   ├── index.php
│   └── .htaccess
│
├── admin/
│   ├── index.php
│   ├── dashboard.php
│   ├── careers/
│   ├── articles/
│   ├── learning-paths/
│   ├── resources/
│   ├── users/
│   └── settings/
│
├── database/
│   ├── schema.sql
│   ├── seed.sql
│   └── migrations/
│
├── includes/
│   ├── header.php
│   ├── footer.php
│   └── functions.php
│
├── storage/
│   ├── logs/
│   └── cache/
│
├── setup/
│   └── install.php
│
├── docs/
│   ├── INSTALLATION.md
│   ├── DEPLOYMENT.md
│   ├── DATABASE.md
│   └── USER_GUIDE.md
│
├── .gitignore
├── README.md
└── index.php
```

This is a recommended starting architecture, not a restriction against improving the structure.

Keep private application files, configuration, logs, and other sensitive information outside the publicly accessible web directory wherever the hosting environment permits.

If a shared hosting environment requires a different document root arrangement, provide a secure deployment configuration for it.

## **10\. XAMPP Local Development Requirements**

The application must work locally without complicated configuration.

Provide a detailed installation process.

Expected local setup:

1. Install and start XAMPP.  
2. Start Apache and MySQL.  
3. Place the project inside:

```
C:\xampp\htdocs\careerpath\
```

4\. Open phpMyAdmin:

```
http://localhost/phpmyadmin
```

5\. Create a database, for example:

```
CREATE DATABASE careerpath_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

6.   
   Import the provided database schema and seed files.  
7. Configure local database credentials in the application configuration.  
8. Open the website:

```
http://localhost/careerpath/
```

The system must support operation from a subdirectory, not just the domain root.

Do not hardcode localhost URLs throughout the application.

Create a configurable base URL and asset URL helper to handle both local and hosted environments.

## **11\. Production Deployment and Hosting Compatibility**

A major requirement is that the same application must work both locally and after deployment.

Design the application to support:

* cPanel shared hosting.  
* Apache-based PHP hosting.  
* MySQL or compatible MariaDB hosting.  
* VPS environments.  
* Standard PHP hosting without Node.js.  
* Domain-root and subdirectory deployments.

Implement environment-aware configuration.

For example:

```
<?php

return [
    'app_name' => 'CareerPath',

    // Set this for the environment where the application is installed.
    'base_url' => 'http://localhost/careerpath',

    'environment' => 'development',

    'database' => [
        'host' => 'localhost',
        'name' => 'careerpath_db',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
];
```

Treat this as a development example. Production credentials and URLs must be configured separately and must never be committed to the public source repository.

Important hosting requirements:

* Support a configurable base URL and document root.  
* Use relative or helper-generated application links.  
* Do not hardcode absolute filesystem paths.  
* Do not assume `.htaccess` or URL rewriting is available.  
* Provide fallback routing using ordinary PHP URLs.  
* Ensure file uploads work with configurable upload directories.  
* Make PHP error display configurable and disable it in production.  
* Include production error logging.  
* Provide deployment instructions for importing the database.  
* Include instructions for setting file and folder permissions.  
* Ensure email functions are configurable for SMTP-capable hosting.  
* Disable or remove the installer after successful installation.  
* Ensure that sensitive files cannot be accessed through public URLs.

Create separate development and production configuration examples.

## **12\. Security and Data Protection**

Apply secure development practices throughout the application.

Implement:

* PDO prepared statements.  
* Password hashing with `password_hash()`.  
* Password verification with `password_verify()`.  
* CSRF protection for state-changing forms.  
* Output escaping to prevent XSS.  
* Input validation and sanitization.  
* Secure session configuration.  
* Session ID regeneration after authentication.  
* Server-side authorization checks.  
* Rate limiting for sensitive endpoints.  
* Secure file upload validation.  
* File type and file size restrictions.  
* Protection against SQL injection.  
* Protection against unauthorized administrative access.  
* Appropriate HTTP security headers.  
* Safe error handling.  
* Audit logging for sensitive administrative activities.  
* Secure password reset procedures.  
* Login attempt monitoring.  
* Safe database transaction handling.

Never store plaintext passwords.

Do not trust user-supplied role values, hidden form fields, file extensions, or JavaScript-only validation.

Protect uploaded files against executable code. Store private uploads outside the public web root where possible.

Add appropriate privacy controls and data deletion procedures.

## **13\. Search, Navigation, and Content Discovery**

Implement a functional platform-wide search.

Users should be able to search:

* Careers.  
* Career categories.  
* Articles.  
* Tutorials.  
* Learning paths.  
* Resources.  
* Community discussions.

Include category filters, relevant sorting, pagination, and helpful empty-state messages.

Use server-side search and filtering for database content.

Make navigation consistent across all public and authenticated pages.

Avoid broken links, unnecessary redirects, and dead-end user journeys.

## **14\. SEO, Performance, and Accessibility**

Build the platform with search engines, performance, and accessibility in mind.

SEO requirements:

* Unique page titles.  
* Meta descriptions.  
* Canonical URLs where appropriate.  
* Clean heading hierarchy.  
* Open Graph metadata.  
* Social sharing metadata.  
* XML sitemap.  
* Robots.txt.  
* Structured data where relevant.  
* Descriptive image alt text.  
* Search-friendly career and article URLs.

Performance requirements:

* Optimized image delivery.  
* Lazy loading for appropriate images.  
* Efficient SQL queries.  
* Database indexes.  
* Pagination for large datasets.  
* Avoid unnecessary database queries.  
* Minimize unnecessary JavaScript and CSS.  
* Browser caching where appropriate.  
* Production asset optimization.

Accessibility requirements:

* Semantic HTML.  
* Keyboard navigation.  
* Accessible form labels.  
* Visible focus indicators.  
* Sufficient colour contrast.  
* Appropriate ARIA attributes.  
* Screen-reader-friendly controls.  
* Responsive layouts.  
* Reduced-motion support.

The website must work properly on desktop computers, tablets, and mobile devices.

## **15\. Optional AI Integration — Future-Ready Architecture**

The first release must work fully without an external AI API.

However, design the system so AI functionality can be introduced later.

Possible future integrations include:

* AI-powered career guidance.  
* AI career profile explanations.  
* Personalized learning recommendations.  
* AI-assisted CV improvement.  
* Interview practice assistance.  
* Skills-gap analysis.  
* Career roadmap generation.  
* AI-assisted learning support.  
* Intelligent content discovery.

Use a modular service architecture so that external AI providers can be connected without rewriting the entire application.

For any future AI integration:

* Keep API keys on the server.  
* Never expose private credentials in JavaScript.  
* Make AI features optional.  
* Validate and moderate input and output.  
* Provide sensible fallback behaviour.  
* Inform users when recommendations are AI-generated.  
* Avoid presenting AI recommendations as guaranteed employment, salary, or career outcomes.

## **16\. Content Quality and Editorial Standards**

The platform's value depends heavily on the quality of its educational content.

Build content management functionality that encourages:

* Clear explanations for beginners.  
* Practical examples.  
* Step-by-step instructions.  
* Realistic career expectations.  
* Relevant learning resources.  
* Updated information.  
* Industry-specific guidance.  
* Actionable exercises.  
* Project-based learning.  
* Ethical and responsible AI education.

Include starter content so the website is not empty after installation.

Create a balanced initial content library with at least:

* 20 career profiles.  
* 10 career categories.  
* 10 structured learning paths.  
* 15 educational articles or tutorials.  
* 10 AI-era career guides.  
* 20 curated learning resources.  
* 10 career development tools.

The content should be original, educational, and realistic. Clearly label sample or demonstration content.

Where career requirements, salary expectations, job outlooks, or certifications vary by country, state the relevant geographical context and avoid presenting estimates as universal facts.

## **17\. Testing and Quality Assurance**

Before declaring the application complete, test every major feature.

Functional testing

* Registration and login.  
* Logout.  
* Password reset.  
* User profile management.  
* Career search.  
* Career filtering.  
* Assessment submission.  
* Career recommendations.  
* Learning path enrollment.  
* Lesson progress tracking.  
* Article publishing.  
* Resource management.  
* Comments and moderation.  
* Administrative permissions.  
* File uploads.  
* Contact forms.  
* Search and pagination.

Database testing

* Foreign key relationships.  
* Duplicate records.  
* Invalid data.  
* Deletion rules.  
* Transactions.  
* Query performance.

Security testing

* SQL injection attempts.  
* XSS payloads.  
* CSRF protection.  
* Unauthorized admin access.  
* Session handling.  
* Upload validation.  
* Password reset security.

Environment testing

Test the same codebase in:

1. Local XAMPP development.  
2. A local installation running from a project subdirectory.  
3. A production-like Apache/PHP/MySQL environment.  
4. A hosted environment using a configured domain and database.

Do not consider the application deployment-ready merely because it works on localhost.

## **18\. Development Workflow and Agent Execution Instructions**

Do not attempt to produce the entire application as one unstructured block of code.

Follow a professional, milestone-based development workflow.

Phase 1 — Requirements Analysis and Planning

* Analyze the project vision.  
* Identify functional requirements.  
* Define user journeys.  
* Define application modules.  
* Identify dependencies and technical limitations.  
* Produce a system architecture.  
* Design the database relationships.  
* Create a development roadmap.

Phase 2 — UI/UX Planning

* Define visual identity.  
* Create page layouts.  
* Establish typography and colours.  
* Design responsive navigation.  
* Plan user and administrator dashboards.  
* Design reusable interface components.

Phase 3 — Project Foundation

* Create the folder structure.  
* Configure PHP and MySQL.  
* Implement the database connection.  
* Build common helper functions.  
* Implement application configuration.  
* Set up secure sessions and authentication foundations.

Phase 4 — Core Backend Development

* Implement database models and repositories.  
* Implement validation.  
* Implement authentication and authorization.  
* Implement career management.  
* Implement content management.  
* Implement learning paths.  
* Implement assessments.  
* Implement resource management.

Phase 5 — Frontend Development

* Build public pages.  
* Build responsive navigation.  
* Develop reusable CSS components.  
* Implement interactive JavaScript.  
* Build user dashboards.  
* Build administrator interfaces.

Phase 6 — Advanced Functionalities

* Develop career assessment tools.  
* Develop skills gap analysis.  
* Develop career roadmaps.  
* Implement learning progress tracking.  
* Implement bookmarks.  
* Implement community features.  
* Implement search and filtering.

Phase 7 — Content and Database Seeding

* Build sample career profiles.  
* Create initial tutorials.  
* Add learning paths.  
* Add educational resources.  
* Populate the homepage.

Phase 8 — Security and Optimization

* Perform security checks.  
* Optimize database queries.  
* Improve accessibility.  
* Optimize frontend assets.  
* Review application error handling.

Phase 9 — Testing

* Run functional tests.  
* Test user permissions.  
* Test all forms.  
* Test database operations.  
* Test responsive layouts.  
* Test local installation and deployment compatibility.

Phase 10 — Documentation and Deployment

* Prepare the README.  
* Prepare XAMPP setup documentation.  
* Prepare production deployment instructions.  
* Prepare database documentation.  
* Prepare administrator documentation.  
* Prepare troubleshooting instructions.

At the end of every phase, provide a summary of completed work, files created or modified, outstanding issues, and the next development milestone.

Do not mark features as complete until they have been implemented and tested.

## **19\. Required Final Deliverables**

The completed project must include:

1. Complete frontend source code.  
2. Complete PHP backend source code.  
3. MySQL database schema.  
4. Database seed data.  
5. Functional authentication and authorization.  
6. User dashboard.  
7. Administration dashboard.  
8. Career discovery module.  
9. AI-era career guidance module.  
10. Career assessment and recommendation system.  
11. Learning paths and tutorials.  
12. Professional development resources.  
13. Interactive career utilities.  
14. Resource management system.  
15. Responsive user interface.  
16. Security implementation.  
17. SEO implementation.  
18. XAMPP installation guide.  
19. Production deployment guide.  
20. Database documentation.  
21. Administrator user guide.  
22. Troubleshooting guide.  
23. Sample administrator setup procedure.  
24. `.gitignore` and safe configuration examples.

The codebase must be organized, commented where necessary, reusable, and maintainable.

Avoid unfinished placeholder functionality, non-working buttons, fake forms, hardcoded user data, and simulated features presented as real implementations.

If a feature is not yet implemented, clearly identify it rather than pretending it works.

## **20\. Final Acceptance Criteria**

The project is considered successfully completed only when:

* The complete application runs locally on XAMPP.  
* The application can be moved to another standard PHP/MySQL hosting environment through configuration changes.  
* The database can be installed using the provided SQL files.  
* User registration, login, and role permissions work.  
* Career profiles and educational content can be managed through the admin dashboard.  
* Users can complete assessments and save relevant results.  
* Users can enroll in learning paths and track their progress.  
* Search, filtering, bookmarking, and interactive tools work.  
* All major pages are responsive.  
* The website follows the stated security requirements.  
* The application does not require a PHP or frontend framework.  
* Documentation explains local installation and live deployment.  
* No sensitive credentials are exposed in the repository.  
* No major broken links, forms, or navigation elements remain.

Final instruction to the coding agent:

Think like a product architect, not just a developer. Build a useful, cohesive, scalable, and maintainable career development platform that can grow into a trusted online destination for career guidance, practical learning, professional development, and navigating the future of work.

Prioritize user experience, practical educational value, data security, maintainability, and cross-environment compatibility.

Start with Phase 1: Requirements Analysis and Technical Architecture. Present the proposed system architecture, database relationship plan, user journeys, core modules, and development milestones before proceeding with implementation.

Do not sacrifice code quality or security for speed.

## **Additional product recommendations**

I recommend treating these as long-term product features rather than trying to force everything into the first release.

[The Standard \| Engineering Readiness](https://images.openai.com/static-rsc-4/UsXL7MVXDc90rxPczB5gP8ffb6lqXM4jyzRVAM11zwKIAhLYkzLQs78MzHLOHxsoKRrvWCKmuy9RxlSxY7GicQtSeePqaJKA3A-AqcQQTuZafxjtf9KjaFvGA5-XK2g8im4PJNjsTR9--eDTAwDT_niuaAB_w0Mz_shRaIKNMug?purpose=inline)

1\. Personalized career dashboard

Each user gets a personal workspace showing career interests, saved careers, learning progress, skill development, and career goals.

[Unlock Your Team’s Potential with Expert AI Training](https://images.openai.com/static-rsc-4/LIj-rqpAMzAWke7gjRcOPjz2sUQbupY7-HkdyVFzKdNGB62pLrRXI9JlgNISrnITGPPdL9vqkstWiwZB_KZ2RC9ZZmMMeG4_UgW_2GqVooVtR4ldcr4b5ml-2dICyUXaPIJYzUWcoxGvPCx0p_s3Pbn3NndtHyLaNxYQUG8AuKE?purpose=inline)

2\. Future Skills Tracker

A dedicated system for monitoring important professional skills, emerging technologies, and learning priorities.

[Portfolio Website \- Personal Developer Portfolio \| Praveen Kumar](https://images.openai.com/static-rsc-4/DaS3b__7YF7Ck3lgsdS94rb2azUQZYC5eubxYI76yMgfVXUklDakjiM1931uds4eWuAwMRY1R9j8GVAgUWNyC2G-gNPmS-sezV4KamAp_ERURritzJIGjO6YdOfSRS9GV6aZE-WRswrMg3Mf083pASL_r7sAuQurMOeuaspu-A4?purpose=inline)

3\. Practical Project Showcase

Users can publish projects, share portfolios, demonstrate their skills, and build professional visibility.

[Case Studies \- Real Mentorship Success Stories \| Mentorly \| Mentorly Mentorship Platform](https://images.openai.com/static-rsc-4/GLPp02fzO43y1V8ay4_RdGzhwK3GgDC06VEG2bSh4zM-8dtuw5wTrqQPQtlKa8WsRBLSMJQGwsb5gFLWs5z2gE7rkU1-xIbNtooQZwvAnZxkbaYzkKDWiJ1zAUG0smct6pD_D1jbCn9LH6tQXjdwTu6B9w2CMzBHeuu70Ceuwl8?purpose=inline)

4\. Mentorship and Counselling

A future module where verified career mentors can offer guidance sessions, subject to appropriate booking and privacy controls.

[Educational Platform Dashboard \- Gamification by Anuj Istwal on Dribbble](https://images.openai.com/static-rsc-4/WRgicfXBtDec0Uut-H2EZHL4dlI8JA9xK25OOcYjjVogBVu6UZDKmBDNZKxnfhdmyMABJxXlIQ_QRzluDylRhraEUP69TAAUoe7r9cuMwb2HUH_9V6rlGw5E3hPabKKZgoH_YOFjYlDKV-dQ2USUv_c_gPn9_kBB70G8BbkGApU?purpose=inline)

5\. Learning Challenges and Achievements

Weekly challenges, learning streaks, milestone badges, and project-based achievements to encourage consistent development.

My architectural recommendation: Build the first release as a modular PHP application with PDO, MySQL, reusable PHP components, and vanilla JavaScript. Keep the AI integration optional and the career recommendation engine functional without AI APIs. This gives you a deployable product on XAMPP and ordinary PHP hosting without introducing unnecessary infrastructure.

The most important principle is to make the platform genuinely useful from day one: visitors should discover careers, registered users should follow measurable learning paths, and administrators should be able to manage the entire educational ecosystem without editing source code.

