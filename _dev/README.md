# _dev/

This folder contains development tooling that ships with the theme for use by developers building on top of Abra. Files here are renamed from their dotfile originals so they pass the WordPress.org theme directory automated checker.

## Before you commit to your project

Rename and move these files back to the theme root:

| Folder/File here | Should become at root |
|---|---|
| `_dev/claude/` | `.claude/` |
| `_dev/github/` | `.github/` |
| `_dev/gitignore` | `.gitignore` |

## Contents

- `claude/` — Claude Code context files (CLAUDE.md commands, slash commands for building WP constructs)
- `github/` — GitHub Actions workflows and issue templates
- `gitignore` — Standard WordPress theme .gitignore rules
