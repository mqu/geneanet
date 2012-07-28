all: pull push

push:
	cp -rpuP * ~/Dropbox/Genealogie/php/geneanet/

pull:
	cp -rpuP ~/Dropbox/Genealogie/php/geneanet/* .

clean:
	rm var/cache/*.html var/cookie.txt

re-dist:
	@make clean
	@rm -fr ~/Dropbox/Genealogie/php/geneanet/
	@mkdir -p ~/Dropbox/Genealogie/php/geneanet/
	@make push
