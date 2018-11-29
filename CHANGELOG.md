# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.3.0] - 2018-11-29
### Added
- allow explicit configuration of logger service
- separate bulked requests in web profiler output
- add a "debug request in Kibana" button in web profiler output which exports a runnable query to a configured Kibana instance

## [0.2.0] - 2018-11-14
### Added
- allow configuration of multiple clients, each with multiple possible nodes
- docs in README for above

## [0.1.2] - 2018-11-13
###
- fixes to web profiler template

## [0.1.1] - 2018-11-12
### Fixed
- allow `TracerLogger` to handle bulk requests and responses

## [0.1.0] - 2018-11-09
### Added
- initial version providing one Elasticsearch client at default location
