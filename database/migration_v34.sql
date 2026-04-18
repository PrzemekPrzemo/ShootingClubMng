-- Migration v34: backfill competitions.is_public = 1 dla istniejących zawodów
--
-- Migracja v26 dodała kolumnę `is_public` z DEFAULT 0. Do tej pory nie była
-- używana w kodzie, więc wszystkie istniejące zawody mają `is_public = 0`.
-- Po multi-tenant hardening (commit 1db30be) portal zawodnika wymaga
-- `is_public = 1` żeby pokazać zawody — w przeciwnym razie po deployu
-- zawodnicy widzą pustą listę „Dostępne zawody" mimo że admin klubu
-- utworzył je wcześniej.
--
-- Backfillujemy wszystkie istniejące (utworzone przed tą migracją)
-- zawody jako publiczne. Nowe zawody admin może utworzyć z flagą
-- opublikowana/szkic wg uznania.

UPDATE `competitions`
   SET `is_public` = 1
 WHERE `is_public` = 0
   AND `competition_date` >= '2000-01-01';
