# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Add PHP 8 support.
- Add PHPUnit 8 and 9 compatibility.

### Changed

- Change the constructor signature of `AbstractApiClient` to make the first parameter non-optional. This modification should not cause any BC breaks because the second parameter was not optional either.

### Removed

- Remove PHP 7.1 compatibility.
- Remove phpunit 7 compatibility.

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
