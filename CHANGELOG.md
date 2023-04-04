# Changelog
All notable changes to this project will be documented in this file.

[0.8.4] - 2023-04-04
- Fixed: avoid null argument

[0.8.3] - 2022-06-09
- Fixed: missing service exception

[0.8.2] - 2022-05-27
- Fixed: invalid namespace
- Fixed: warning in php 8

[0.8.1] - 2022-05-27
- Changed: allow php 8
- Changed: refactored bundle for 4.13
- Deprecated: huh.dc_multilingual_utils.util.dc_multilingual_util service alias

[0.8.0] - 2022-05-05
- Changed: minimum contao version is now 4.13

[0.7.2] - 2020-12-17
- fixed insert tags for translatable alias

[0.7.1] - 2020-09-23
- added alias for DcMultilingualUtil in service.yml

[0.7.0] - 2020-09-23
- overridden dc_multilingual v4+ id handling of translated records

[0.6.0] - 2020-09-22
- added new inserttags `dcmu_news_url`, `dcmu_event_url`, `dcmu_faq_url`

[0.5.2] - 2020-09-04
- fixed class dependency in ContentListener eventlistener
