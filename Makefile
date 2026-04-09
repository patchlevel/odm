help:                                                                           ## shows this help
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_\-\.]+:.*?## / {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

vendor: composer.lock
	composer install

.PHONY: phpcs-check
cs-check: vendor                                                                ## run phpcs
	vendor/bin/phpcs

.PHONY: cs
cs: vendor                                                                      ## run phpcs fixer
	vendor/bin/phpcbf || true
	vendor/bin/phpcs

.PHONY: phpstan
phpstan: vendor                                                                 ## run phpstan static code analyser
	vendor/bin/phpstan analyse --memory-limit=-1

.PHONY: phpstan-baseline
phpstan-baseline: vendor                                                        ## run phpstan static code analyser
	vendor/bin/phpstan analyse --generate-baseline --memory-limit=-1

.PHONY: phpunit
phpunit: vendor phpunit-unit phpunit-integration                              	## run phpunit tests

.PHONY: phpunit-integration
phpunit-integration: vendor                                                    	## run phpunit integration tests
	MONGODB_URI="mongodb://localhost:27017" POSTGRES_URI="pgsql:host=localhost;port=5432;dbname=eventstore;user=postgres;password=postgres" vendor/bin/phpunit --testsuite=integration

.PHONY: phpunit-integration-postgres
phpunit-integration-postgres: vendor                                            ## run phpunit integration tests on postgres
	POSTGRES_URI="pgsql:host=localhost;port=5432;dbname=eventstore;user=postgres;password=postgres" vendor/bin/phpunit --testsuite=integration

.PHONY: phpunit-integration-mongodb
phpunit-integration-mongodb: vendor                                               ## run phpunit integration tests on mysql
	MONGODB_URI="mongodb://localhost:27017" vendor/bin/phpunit --testsuite=integration

.PHONY: phpunit-unit
phpunit-unit: vendor                                             				## run phpunit unit tests
	XDEBUG_MODE=coverage vendor/bin/phpunit --testsuite=unit

.PHONY: infection
infection: vendor                                                               ## run infection
	XDEBUG_MODE=coverage vendor/bin/infection --threads=3

.PHONY: static
static: phpstan cs                                               				## run static analysers

test: phpunit                                                                   ## run tests

.PHONY: benchmark
benchmark: vendor                                                               ## run benchmarks
	vendor/bin/phpbench run tests/Benchmark --report=default

.PHONY: benchmark-diff-test
benchmark-diff-test: vendor                                                          ## run benchmarks
	vendor/bin/phpbench run tests/Benchmark --revs=1 --report=default --progress=none --tag=base
	vendor/bin/phpbench run tests/Benchmark --revs=1 --report=diff --progress=none --ref=base


.PHONY: dev
dev: static test                                                                ## run dev tools
