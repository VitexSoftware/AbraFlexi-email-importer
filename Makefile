repoversion=$(shell LANG=C aptitude show multi-abraflexi-setup | grep Version: | awk '{print $$2}')
nextversion=$(shell echo $(repoversion) | perl -ne 'chomp; print join(".", splice(@{[split/\./,$$_]}, 0, -1), map {++$$_} pop @{[split/\./,$$_]}), "\n";')

all:

clean:
	rm -rf docs


phpdoc: clean
	mkdir -p docs
	phpdoc --defaultpackagename=MainPackage -d src
	mv .phpdoc/build/* docs

deb:
	debuild -us -uc

release:
	echo Release v$(nextversion)
	dch -v $(nextversion) `git log -1 --pretty=%B | head -n 1`
	debuild -i -us -uc -b
	git commit -a -m "Release v$(nextversion)"
	git tag -a $(nextversion) -m "version $(nextversion)"
