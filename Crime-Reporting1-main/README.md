# Crime Reporting and Incident Management System

This project is a web-based Crime Reporting and Incident Management System built for citizens and administrators. It allows users to submit non-emergency complaints online, attach evidence, mark locations on a map, track complaint progress, and view public crime statistics. Administrators can review complaints, update case status, and monitor reporting activity through a dashboard.

## Project Purpose

The main goal of this project is to digitize the non-emergency crime reporting process and make it easier for:

- citizens to report incidents safely and quickly
- administrators to manage complaints in one place
- the public to view complaint trends and analytics

This system is designed as a smart city style portal with a modern UI, guided onboarding, chatbot assistance, and multi-page language switching.

## Main Modules

### 1. Homepage

The homepage introduces the system and gives users quick access to:

- Report Crime
- Track Complaint
- Public Statistics
- Chatbot help
- Language switching
- Guided website tour

### 2. User Module

The user side includes:

- user registration
- user login
- report crime form
- dashboard
- complaint tracking
- evidence preview
- notifications section

### 3. Admin Module

The admin side includes:

- admin login
- complaint management dashboard
- complaint detail page
- complaint filtering and review
- status update actions
- complaint deletion support

### 4. Public Statistics Module

This page shows:

- total complaints
- pending complaints
- complaints under investigation
- resolved complaints
- crime type distribution
- monthly complaint trends
- public status mix charts

### 5. Chatbot Module

The chatbot provides support for common user questions such as:

- how to report a crime
- how to upload evidence
- how to track a complaint
- what to do in an emergency

It works even without Ollama installed because manual fallback replies were added in the PHP API.

### 6. Guided Tour Module

The system includes an onboarding guide for new users. It:

- shows automatically for first-time visitors
- highlights important sections step by step
- can be replayed manually using the Replay Guide button

## Features Implemented

The following features are currently implemented:

- secure session handling
- session timeout support
- CSRF protection
- flash message support
- user registration and login
- admin login
- non-emergency complaint submission
- complaint evidence upload
- GPS and map-based location selection
- address search using OpenStreetMap and Nominatim
- complaint tracking dashboard
- public analytics dashboard
- chatbot with manual fallback answers
- Google Translate based language switching
- onboarding coachmark style guide
- modern responsive UI
- shared design system across pages

## Technology Stack

### Frontend

- HTML
- CSS
- Bootstrap 5
- JavaScript
- Font Awesome
- Chart.js
- Leaflet.js

### Backend

- PHP
- MySQL

### Other Services

- OpenStreetMap tiles
- Nominatim geocoding
- Google Translate widget

## Project Structure

```text
Crime-Reporting1-main/
|-- index.php
|-- public_stats.php
|-- README.md
|-- config/
|   |-- db.php
|   |-- security.php
|-- includes/
|   |-- footer.php
|-- assets/
|   |-- css/app.css
|   |-- js/app.js
|   |-- js/google-translate-switcher.js
|   |-- js/site-tour.js
|   |-- img/
|-- chatbot/
|   |-- chatbot.css
|   |-- chatbot.js
|   |-- chatbot_api.php
|-- user/
|   |-- login.php
|   |-- register.php
|   |-- report.php
|   |-- dashboard.php
|   |-- logout.php
|-- admin/
|   |-- login.php
|   |-- dashboard.php
|   |-- complaints.php
|   |-- complaint_details.php
|   |-- update_status.php
|   |-- delete_complaint.php
|   |-- logout.php
```

## Database Purpose

The database stores:

- registered users
- admin records
- complaint details
- uploaded evidence file names
- complaint status
- created timestamps
- optional notifications

The project already includes SQL files:

- [database_setup.sql](/d:/Xampp/htdocs/Crime-Reporting1-main2222/Crime-Reporting1-main/database_setup.sql)
- [database_improvements.sql](/d:/Xampp/htdocs/Crime-Reporting1-main2222/Crime-Reporting1-main/database_improvements.sql)

## Complaint Workflow

The complaint workflow is:

1. User registers or logs in.
2. User opens the Report Crime page.
3. User selects crime type.
4. User writes a description.
5. User selects the location on the map.
6. User uploads evidence if available.
7. User submits the complaint.
8. Complaint is saved with default status `Pending`.
9. User tracks the complaint in the dashboard.
10. Admin reviews and updates the complaint.

## Security Features

Security measures included in the project:

- secure session start
- session timeout enforcement
- CSRF token protection
- access control for user/admin pages
- input trimming and validation
- password hashing support
- restricted file type upload for evidence

## UI and UX Improvements Added

The project was improved with:

- modern glass-style navbar
- polished cards and section styling
- stronger shared visual system
- responsive layout improvements
- homepage service hub section
- moving helpline strip
- chatbot theme styling
- custom chatbot logo
- onboarding guide for new users
- replay guide button

## Language Switching

The project uses Google Translate for easy language switching.

Supported now:

- English
- Hindi

This was chosen so the full page content can be translated without manually maintaining separate translation files for every page.

## Chatbot Logic

The chatbot uses:

- frontend widget from `chatbot/chatbot.js`
- styles from `chatbot/chatbot.css`
- backend handler from `chatbot/chatbot_api.php`

Important detail:

- If Ollama is not installed, the chatbot still works with manual fallback answers.
- Most asked questions are provided as quick buttons inside the chatbot.

## Demo Notes

For demo presentation, you can explain:

- this system is for non-emergency complaints only
- emergency users must call `100` or `112`
- the chatbot is assistant-based, not a full police dispatch system
- language switching works using Google Translate
- guided onboarding helps first-time users understand the portal

### Demo Flow Suggestion

Use this order in your project demo:

1. Open homepage
2. Show Replay Guide
3. Show language switch
4. Register or login as citizen
5. Submit a complaint from Report Crime
6. Show dashboard and complaint tracking
7. Show public statistics
8. Login as admin
9. Show complaint management and status update
10. Show chatbot quick questions

## Examiner Questions You May Be Asked

### What problem does this project solve?

It digitizes crime complaint intake for non-emergency situations and helps both citizens and administrators manage the reporting process more efficiently.

### Why is this project useful?

It reduces manual reporting dependency, improves tracking transparency, centralizes complaint handling, and gives users better access to reporting tools.

### Why did you use PHP and MySQL?

PHP and MySQL are simple, practical, widely supported, and suitable for college-level web application projects with CRUD operations, authentication, and admin workflows.

### Why did you use Leaflet and OpenStreetMap?

They provide map-based location capture without depending on paid map APIs, which makes them good for an academic project.

### Why did you use Google Translate instead of manual translations?

It allows automatic full-page translation across all pages without maintaining separate Hindi versions of every text block.

### What happens if Ollama is not installed?

The chatbot still works because manual fallback answers are returned from the PHP backend for common user queries.

### How is security handled?

The project uses session protection, CSRF protection, authenticated page access, timeout handling, validation, and restricted evidence upload types.

### What is the difference between user and admin modules?

Users can submit and track complaints. Admins can review all complaints, inspect complaint details, and update or manage case status.

### What is the purpose of the public statistics page?

It gives a high-level public view of complaint patterns and trends without exposing private user-specific case information.

### Why did you add the guided tour?

It improves onboarding and helps new users understand how to use the portal without external instructions.

## Current Limitations

Important limitations to mention honestly:

- Google Translate depends on internet availability
- map search and reverse geocoding depend on external services
- chatbot fallback answers are rule-based, not full conversational AI
- this system is designed for non-emergency reporting only
- production deployment would require stronger server hardening and logging

## Future Scope

Future improvements could include:

- real SMS or email notifications
- complaint assignment to departments
- FIR workflow integration
- OTP-based verification
- multilingual manual translation system
- live chat with support operator
- advanced AI-assisted complaint classification
- downloadable complaint receipt PDF

## How to Run

1. Place the project inside XAMPP `htdocs`.
2. Start Apache and MySQL from XAMPP.
3. Import the SQL setup file into MySQL.
4. Update database credentials in [config/db.php](/d:/Xampp/htdocs/Crime-Reporting1-main2222/Crime-Reporting1-main/config/db.php) if needed.
5. Open the project in browser using localhost.

Example:

```text
http://localhost/Crime-Reporting1-main2222/Crime-Reporting1-main/index.php
```

## Important Files for Viva or Explanation

- [index.php](/d:/Xampp/htdocs/Crime-Reporting1-main2222/Crime-Reporting1-main/index.php)
- [user/report.php](/d:/Xampp/htdocs/Crime-Reporting1-main2222/Crime-Reporting1-main/user/report.php)
- [user/dashboard.php](/d:/Xampp/htdocs/Crime-Reporting1-main2222/Crime-Reporting1-main/user/dashboard.php)
- [public_stats.php](/d:/Xampp/htdocs/Crime-Reporting1-main2222/Crime-Reporting1-main/public_stats.php)
- [admin/dashboard.php](/d:/Xampp/htdocs/Crime-Reporting1-main2222/Crime-Reporting1-main/admin/dashboard.php)
- [chatbot/chatbot_api.php](/d:/Xampp/htdocs/Crime-Reporting1-main2222/Crime-Reporting1-main/chatbot/chatbot_api.php)
- [config/security.php](/d:/Xampp/htdocs/Crime-Reporting1-main2222/Crime-Reporting1-main/config/security.php)
- [assets/css/app.css](/d:/Xampp/htdocs/Crime-Reporting1-main2222/Crime-Reporting1-main/assets/css/app.css)

## Final Summary

This project is a complete web-based complaint reporting portal that combines:

- citizen reporting
- complaint tracking
- admin management
- public statistics
- guided onboarding
- chatbot support
- language switching
- modern UI design

It is suitable as a final year or academic web development project because it demonstrates authentication, CRUD workflow, admin control, external API usage, data visualization, security practices, and frontend UX improvements in one system.
