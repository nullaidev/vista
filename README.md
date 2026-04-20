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
vendor/bin/vista install-skill
```

Pass `--scope=user` to install into your home directory so every project picks it up. See `vendor/bin/vista --help` for finer-grained targets and flags.

### Using the skill

Start (or restart) your agent session after installing so it picks up the new skill. Most tools auto-load the skill once your prompt or the files you're editing match its description — if you'd rather trigger it explicitly, paste this at the top of your conversation:

```
Use the vista skill to help me work with the Nullai Vista PHP view engine
(new View(...), ViewRenderEngine, $this->layout(), $this->section(),
$this->include(), and templates under the views/ folder).
```

In **Claude Code** you can also just type `/vista` to invoke it directly.

## Security Vulnerabilities

If you discover a security vulnerability within Vista, please submit an issue on GitHub. All security vulnerabilities will be promptly addressed.

## License

The Vista PHP view rendering utility is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).