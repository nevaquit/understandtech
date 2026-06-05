export default function Home() {
  return (
    <div className="flex flex-1 flex-col items-center justify-center bg-zinc-50 px-6 py-24 font-sans dark:bg-black">
      <main className="flex w-full max-w-2xl flex-col items-center gap-8 text-center">
        <div className="flex flex-col gap-3">
          <p className="text-sm font-medium uppercase tracking-[0.2em] text-zinc-500">
            Local development
          </p>
          <h1 className="text-4xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">
            Understandtech.app
          </h1>
          <p className="text-lg leading-8 text-zinc-600 dark:text-zinc-400">
            Full-stack Next.js app with API routes, TypeScript, and Tailwind CSS.
          </p>
        </div>

        <div className="flex flex-col gap-3 rounded-2xl border border-zinc-200 bg-white p-6 text-left text-sm text-zinc-600 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-400 sm:flex-row sm:gap-8">
          <div>
            <p className="font-medium text-zinc-950 dark:text-zinc-50">Frontend</p>
            <p className="mt-1">Edit `src/app/page.tsx`</p>
          </div>
          <div>
            <p className="font-medium text-zinc-950 dark:text-zinc-50">API</p>
            <p className="mt-1">
              Try{" "}
              <a
                href="/api/health"
                className="font-medium text-zinc-950 underline underline-offset-4 dark:text-zinc-50"
              >
                /api/health
              </a>
            </p>
          </div>
        </div>
      </main>
    </div>
  );
}
