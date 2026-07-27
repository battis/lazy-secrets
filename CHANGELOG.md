# Changelog

All notable changes to this project will be documented in this file. See [commit-and-tag-version](https://github.com/absolute-version/commit-and-tag-version) for commit guidelines.

## [1.1.0](https://github.com/battis/lazy-secrets/compare/ts/1.0.4...ts/1.1.0) (2026-07-27)


### Features

* configurable number of versions to retain on update (default 1) ([68ace07](https://github.com/battis/lazy-secrets/commit/68ace077f2c3d445afedd8aa4a43b903a0297437))


### Bug Fixes

* limit retention check to enabled versions ([865b5c4](https://github.com/battis/lazy-secrets/commit/865b5c46be58cfc03dd4b5445132e8589740ff82))
* skip SecretManagerClient initialization during Next.js production build ([2ce9693](https://github.com/battis/lazy-secrets/commit/2ce969326975539fd09543b5555358f37c3cb64d))
* use and cache projectId from opts, if provided ([56db862](https://github.com/battis/lazy-secrets/commit/56db86279cbd5bf4e6ddbb10b598029c552211ed))

## [1.0.4](https://github.com/battis/lazy-secrets/compare/ts/1.0.3...ts/1.0.4) (2026-05-13)


### Bug Fixes

* expose init() for general use ([fcd9fe3](https://github.com/battis/lazy-secrets/commit/fcd9fe3da6cc66f23ba61486b39ae36375d8b582))

## [1.0.3](https://github.com/battis/lazy-secrets/compare/ts/1.0.2...ts/1.0.3) (2026-05-12)


### Bug Fixes

* explicitly instantiate client using env var GOOGLE_CLOUD_PROJECT ([56bbbfa](https://github.com/battis/lazy-secrets/commit/56bbbfa4fdca03071eee78153ce51156af7cde88))

## [1.0.2](https://github.com/battis/lazy-secrets/compare/ts/1.0.1...ts/1.0.2) (2026-05-12)


### Bug Fixes

* wrap dynamic declaration in function call ([c309192](https://github.com/battis/lazy-secrets/commit/c309192f1e1c7544b174c1ed3f7aa835bce170b3))

## [1.0.1](https://github.com/battis/lazy-secrets/compare/ts/1.0.0...ts/1.0.1) (2026-05-12)


### Bug Fixes

* compile against Node LTS explicitly ([59bec2d](https://github.com/battis/lazy-secrets/commit/59bec2db20c8fbb192e566a04f3f2602b38170d5))

## 1.0.0 (2026-05-12)
