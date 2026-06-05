# Understandtech.app

Local full-stack web application built with Next.js, TypeScript, and Tailwind CSS.

## Stack

- **Frontend:** React 19 + Next.js App Router
- **Backend:** Next.js API routes (`src/app/api`)
- **Styling:** Tailwind CSS v4

## Getting started

```bash
npm install
cp .env.example .env.local
npm run dev
```

Open [http://localhost:3000](http://localhost:3000). The health check API is at [http://localhost:3000/api/health](http://localhost:3000/api/health).

## Scripts

| Command        | Description              |
| -------------- | ------------------------ |
| `npm run dev`  | Start development server |
| `npm run build`| Production build         |
| `npm run start`| Run production server    |
| `npm run lint` | Run ESLint               |

## Project structure

```
src/
  app/
    api/          # Backend API routes
    page.tsx      # Home page
    layout.tsx    # Root layout
public/           # Static assets
```
