PHAR = webpage_consolidator.phar

SOURCE = \
	simplehtmldom/simple_html_dom.php \
	WebPageConsolidator.inc.php \
	WebpageCache.inc.php \
	index.php

$(PHAR): $(SOURCE)
	@echo "1. Packaging source files..."
	@mkdir src
	@cp -a Web* index.php simplehtmldom src

	@echo "2. Generating certificates..."
	@mkdir cert
	@cd cert; phar-generate-cert

	echo "3. Creating $(PHAR) phar..."
	@phar-build --phar $(PHAR)
clean:
	@echo "Cleaning up..."
	@rm -rf src cert webpage_consolidator.phar*

dist:
	rm -rf src cert

