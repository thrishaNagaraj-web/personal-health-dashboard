# 🏥 HealthDash — Personal Health Dashboard

## Features
- Weight, water, calorie, exercise tracking
- Sleep and mood logging
- Health Score with animated ring
- AI-powered weekly insights (Groq)
- Goals management
- CSV/PDF export
- Dark/light mode
- Responsive design

## Tech Stack
- PHP 8+ (no framework)
- SQLite (via PDO)
- Chart.js
- Groq API (llama-3.1-8b-instant)
- Deployed on Render

## Local Setup
1. Clone the repo
2. Install PHP 8+ with SQLite extension
3. Copy `.env.example` to `.env` and add `GROQ_API_KEY`
4. Run: `php -S localhost:8000`
5. Open: `http://localhost:8000`
6. Register an account and start logging!

## Environment Variables
| Variable | Description |
|----------|-------------|
| GROQ_API_KEY | Your free key from console.groq.com |
| DB_PATH | Path to SQLite file (default: includes/database.sqlite) |

## Deployment (Render)
1. Push to GitHub
2. Create new Web Service on render.com
3. Set environment variables in Render dashboard
4. Deploy!

## Screenshots
Wait for the application to be locally configured and then screenshot via OS capabilities the default layouts encompassing dashboard routing! 

## License
MIT
