# Vista

A brand new, blazing-fast, and ultra-lightweight PHP view engine designed for modern PHP 8.4+ applications.

[Read Full Documentation](https://docs.nullai.com/vista/overview)

## Overview

Vista is a minimalist PHP view engine that focuses on performance and simplicity. It provides powerful tools to manage layouts, partials/includes, and dynamic content rendering, making it an ideal choice for modern PHP projects. Unlike traditional template engines like Blade or Twig, Vista does not rely on compiling or caching template files. This makes it an excellent option for developers who prioritize performance and need a straightforward solution for managing views.

## Key Features

Vista is only five files and very capable:

- **No Compilation Required**: Renders views directly which makes debugging simple.
- **Lightning Fast**: Optimized for speed, with minimal overhead.
- **Extremely Lightweight**: Small footprint, easy to integrate with any PHP application.
- **Modern Syntax**: Intuitive, clean, and developer-friendly APIs.
- **Layout Management**: Easily create reusable layouts and templates.
- **Partial Rendering**: Modularize your views with include and section methods.
- **Scoped Data Passing**: Pass variables to views with isolated scopes for security and clarity.
- **Extensible**: Works seamlessly with other PHP frameworks or custom solutions.

## Installation

To get started with Vista, you need to have PHP 8.4 installed on your system. You can install Vista via Composer. Run the following command in your terminal:

```
composer require nullaidev/vista
```

https://packagist.org/packages/nullaidev/vista

[Read Full Documentation](https://docs.nullai.com/v1/vista/overview)

## AI Skill

Vista ships with an [agentskills.io](https://agentskills.io)-compliant skill that teaches your assistant how the view engine works — path resolution, layouts, sections, includes, `$parent`, and the exception taxonomy. It works with Claude Code, OpenAI Codex, Cursor, Gemini CLI, OpenHands, Goose, and any other tool that reads the `SKILL.md` convention.

Install it for every tool in one command:

```
vendor/bin/vista install-skill --scope=project
```

Swap `--scope=user` to install into your home directory so every project picks it up. See `vendor/bin/vista --help` for finer-grained targets and flags.

### Using the skill

Most tools pick up skills automatically once they exist on disk — start (or restart) a fresh agent session after installing, and the skill becomes available. Trigger it by working on code that touches Vista (e.g. open a file that uses `new View(...)`, `$this->layout()`, or anything under `views/`), or mention Vista in your prompt — the skill's description matches and the agent loads it.

If you want to invoke it manually:

- **Claude Code** — type `/vista` in the chat.
- **OpenAI Codex / Cursor / Gemini CLI** — ask the agent to "use the vista skill" (or equivalent phrasing supported by your tool).

## Security Vulnerabilities

If you discover a security vulnerability within Vista, please submit an issue on GitHub. All security vulnerabilities will be promptly addressed.

## License

The Vista PHP view rendering utility is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).