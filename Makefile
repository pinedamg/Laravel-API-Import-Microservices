.PHONY: setup
setup:
	@echo "Setting up the project for the first time..."
	@if [ ! -f .env ]; then \
		cp .env.example .env; \
		echo "\nCreated .env file from .env.example. Please review it, update database credentials if necessary, and then run 'make setup' again.\n"; \
		exit 1; \
	fi
	# 1. Install composer dependencies (including laravel/sail)                                                                                                                                                              │
	docker run --rm \
		-v "$(PWD):/opt" \
		-w /opt \
		laravelsail/php82-composer:latest \
		composer install --ignore-platform-reqs
	# 2. Build and start containers (this runs composer install)
	./vendor/bin/sail up -d --build
	@echo "Waiting 20 seconds for containers to be ready..."
	@sleep 20
	# Set permissions for storage and bootstrap/cache
	./vendor/bin/sail exec laravel.test chown -R sail:sail storage bootstrap/cache
	./vendor/bin/sail exec laravel.test chmod -R 775 storage bootstrap/cache
	# 2. Generate application key
	./vendor/bin/sail artisan key:generate
	# 3. Install Octane server (RoadRunner binary) and publish config
	./vendor/bin/sail artisan octane:install --server=roadrunner
	./vendor/bin/sail artisan vendor:publish --tag=octane-config
	# 4. Make RoadRunner executable
	./vendor/bin/sail exec laravel.test chmod +x ./rr
	# 5. Install Horizon configuration
	./vendor/bin/sail artisan horizon:install
	# 6. Run database migrations
	./vendor/bin/sail artisan migrate
	# 7. Generate Swagger documentation
	./vendor/bin/sail artisan l5-swagger:generate
	@echo "\nSetup complete! The application is running."
	@echo "You can access it at http://localhost:$(shell grep APP_PORT .env | cut -d '=' -f2)"
	@echo "You can access it to horizon dashboard at http://localhost:$(shell grep APP_PORT .env | cut -d '=' -f2)/horizon"
	@echo "You can access it to swagger http://localhost:$(shell grep APP_PORT .env | cut -d '=' -f2)/api/documentation"
	@echo "You can access it to event list http://localhost:$(shell grep APP_PORT .env | cut -d '=' -f2)/events/"

run:
	./vendor/bin/sail up -d

stop:
	./vendor/bin/sail stop

lint:
	./vendor/bin/sail pint

test:
	./vendor/bin/sail test

# Load Testing Profiles
test-load-light:
	./vendor/bin/sail exec k6 k6 run -e VUS=5 -e DURATION=20s search-api-test.js

test-load: test-load-medium

test-load-medium:
	./vendor/bin/sail exec k6 k6 run -e VUS=20 -e DURATION=45s search-api-test.js

test-load-heavy:
	./vendor/bin/sail exec k6 k6 run -e VUS=100 -e DURATION=2m search-api-test.js

test-load-extreme:
	./vendor/bin/sail exec k6 k6 run extreme-load-test.js

compare-apis:
	@echo "Comparing API performance..."
	$(eval APP_PORT := $(shell grep APP_PORT .env | cut -d '=' -f2))
	@echo "External Provider API:"
	@curl -o /dev/null -s -w 'Tiempo total (Proveedor Externo): %{time_total}s\n' https://provider.code-challenge.feverup.com/api/events
	@echo "Local API (via Octane):"
	@curl -o /dev/null -s -w 'Tiempo total (Nuestra API Local): %{time_total}s\n' 'http://localhost:8088/api/search?starts_at=2021-01-01T00:00:00Z&ends_at=2021-12-31T23:59:59Z'

destroy:
	./vendor/bin/sail down -v --rmi all --remove-orphans
	@echo "Project destroyed. You can run 'make setup' to start fresh."