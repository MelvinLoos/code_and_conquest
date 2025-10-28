# Code & Conquest AI Agent Instructions

This is a gamified coding challenge platform built with Symfony 7.3, where students solve programming puzzles as "Netrunners" in a cyberpunk-themed environment.

## Key Architecture Components

### Core Game Mechanics
- Player characters (`src/Entity/PlayerCharacter.php`):
  - Attributes: level, gold, energy, stats
  - Class system: Netrunner, Data Miner, Sleuth each with unique stat distributions
  - API key authentication for accessing game endpoints

- Mission System (`src/Controller/Api/MissionController.php`):
  - Generated challenges with difficulty tiers (easy/medium/hard)
  - Energy cost/cooldown mechanics for mission generation
  - Victory tokens awarded for successful completions

### Service Layer
- `ChallengeService`: Generates coding puzzles like sum calculations, cipher decryption, and file path traversal
- `CharacterStatsService`: Manages character class stats and abilities
- Custom rate limiting on API endpoints to prevent abuse

## Development Workflow

### Local Setup
```bash
# Start the development environment
docker compose up -d

# Install dependencies
docker compose exec php composer install

# Set up the database
docker compose exec php bin/console doctrine:migrations:migrate
```

### Key Command
Create test characters via CLI:
```bash
bin/console app:create-character "CharacterName" [netrunner|data_miner|sleuth]
```

### Testing
PHPUnit tests configured in `phpunit.dist.xml`. Use `bin/phpunit` to run tests.

## Common Patterns

### API Authentication
- Uses Bearer token auth with custom `ApiTokenAuthenticator`
- Example header: `Authorization: Bearer {character_api_key}`

### Rate Limiting
- Mission-related endpoints: 10 requests/minute per API key
- Registration: 5 attempts/hour per IP
- Configuration in `config/packages/rate_limiter.yaml`

### Database Interactions
- Uses Doctrine ORM with MySQL
- Entity annotations for mapping
- Repository pattern for queries
- Migrations in `migrations/` directory

### Front-end
- Asset management via Symfony Asset Mapper
- Stimulus for JS interactions
- Turbo for enhanced navigation
- CSRF protection for forms

## Integration Points

### Email System
- Welcome emails sent on registration
- Configured via `MAILER_DSN` with Mailjet support
- Templates in `templates/email/`

### API Documentation
- Swagger/OpenAPI docs via NelmioApiDocBundle
- Available at `/api/doc`
- Security schemes defined for bearer auth

## Project-Specific Conventions

### Character Stats
- Five core attributes: sysKnowledge, analytics, interface, secOps, peopleSkills
- Stats range: [8-18] varying by character class
- See `CharacterStatsService` for distribution templates

### Mission Design
1. Board Generation: 3 missions of varying difficulty
2. Energy cost: 10 units to refresh mission board
3. Cooldown: 5 minutes between successful missions
4. Rewards scale with difficulty: 50-150 gold × difficulty multiplier

### Error Handling
- Custom API error responses with meaningful messages
- Energy penalties for failed mission attempts
- Rate limiting with contextual error messages