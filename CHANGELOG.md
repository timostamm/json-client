# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


## [3.2.0] - 2025-09-30

### Added

- Add array support for DeserializedResponse


## [3.1.0] - 2025-09-15

### Added

- Add support for 8.4
- Add PHP 8.4 to the GitHub Actions CI test matrix
- Bump PHPUnit to version 9.6.20 for PHP 8.4 compatibility


## [3.0.0] - 2025-06-15

### Added

- Add Guzzle 7 support
- Add Symfony 5 and 6 support
- Add unit tests for middleware classes

### Changed

- **Breaking:** Refactor deserialization middleware for compatibility with Guzzle 7. `DeserializeResponseMiddleware` now always returns a `Psr\Http\Message\ResponseInterface`. Previously, it returned the deserialized data directly. Refer to the updated [README.md](README.md) for guidance on using the new middleware behavior.
- Replace deprecated `stream_for` usage with `Utils::streamFor`
- Replace deprecated `rejection_for` with `Create::rejectionFor`

### Removed

- **Breaking:** Remove PHP 7 compatibility
- **Breaking:** Remove Symfony 4 support


## [2.1.0] - 2023-04-17

### Added

- Add PHP 8 support
- Add PHPUnit 8 and 9 compatibility
- Add CI via github actions
- Add guzzle IDN_CONVERSION feature
- Add phpunit dependencies

### Changed

- Change the constructor signature of `AbstractApiClient` to make the first parameter non-optional. This modification should not cause any BC breaks because the second parameter was not optional either.
- Update phpunit.xml.dist

### Removed

- Remove PHP 7.1 compatibility
- Remove phpunit 7 compatibility
- Delete composer.lock
- Remove travis configuration

## [2.0.2] - 2018-10-24

### Deprecated

- `HttpLoggerInterface::logStart()` is deprecated and will be removed in 3.0.0. Modifying the response can lead to unexpected result because of required middleware order for logging. Method will be removed.

## [2.0.1] - 2018-09-06

### Fixed

- Bugfix in `ServerMessageMiddleware`: Typo fixed. If response could not be decoded from json, a TypeError was raised.
 
## [2.0.0] - 2018-09-03

### Changed

## [2.0.1] - 2018-09-06

### Fixed

- Move to middleware: All functionality is now implemented as middleware.

### BC Breaks

- `UnexpectedResponseException` now inherits from `BadResponseException`.
- `AbstractApiClient::expectResponseType` and `deserializeResponse` are replaced by middleware.

## [1.0.0] - 2018-04-14

- Initial release
