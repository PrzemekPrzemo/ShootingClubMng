-- ============================================================
-- Naprawia birth_date i gender na podstawie numeru PESEL
-- Pomija rekordy z błędnym lub brakującym PESEL.
--
-- KROK 1: Uruchom SELECT poniżej aby zobaczyć podgląd zmian
-- KROK 2: Uruchom UPDATE aby zapisać zmiany
-- ============================================================


-- ============================================================
-- KROK 1: PODGLĄD — co zostanie zmienione (nic nie zapisuje)
-- ============================================================

SELECT
    m.id,
    m.member_number,
    CONCAT(m.first_name, ' ', m.last_name) AS pelne_imie,
    m.pesel,

    -- aktualne wartości
    m.birth_date                            AS aktualna_data_urodzenia,
    m.gender                                AS aktualna_plec,

    -- wartości z PESEL
    STR_TO_DATE(CONCAT(
        CASE
            WHEN CAST(SUBSTRING(m.pesel,3,2) AS UNSIGNED) BETWEEN 81 AND 92
                THEN 1800 + CAST(SUBSTRING(m.pesel,1,2) AS UNSIGNED)
            WHEN CAST(SUBSTRING(m.pesel,3,2) AS UNSIGNED) BETWEEN  1 AND 12
                THEN 1900 + CAST(SUBSTRING(m.pesel,1,2) AS UNSIGNED)
            WHEN CAST(SUBSTRING(m.pesel,3,2) AS UNSIGNED) BETWEEN 21 AND 32
                THEN 2000 + CAST(SUBSTRING(m.pesel,1,2) AS UNSIGNED)
            WHEN CAST(SUBSTRING(m.pesel,3,2) AS UNSIGNED) BETWEEN 41 AND 52
                THEN 2100 + CAST(SUBSTRING(m.pesel,1,2) AS UNSIGNED)
            WHEN CAST(SUBSTRING(m.pesel,3,2) AS UNSIGNED) BETWEEN 61 AND 72
                THEN 2200 + CAST(SUBSTRING(m.pesel,1,2) AS UNSIGNED)
        END,
        '-',
        LPAD(CASE
            WHEN CAST(SUBSTRING(m.pesel,3,2) AS UNSIGNED) BETWEEN 81 AND 92
                THEN CAST(SUBSTRING(m.pesel,3,2) AS UNSIGNED) - 80
            WHEN CAST(SUBSTRING(m.pesel,3,2) AS UNSIGNED) BETWEEN  1 AND 12
                THEN CAST(SUBSTRING(m.pesel,3,2) AS UNSIGNED)
            WHEN CAST(SUBSTRING(m.pesel,3,2) AS UNSIGNED) BETWEEN 21 AND 32
                THEN CAST(SUBSTRING(m.pesel,3,2) AS UNSIGNED) - 20
            WHEN CAST(SUBSTRING(m.pesel,3,2) AS UNSIGNED) BETWEEN 41 AND 52
                THEN CAST(SUBSTRING(m.pesel,3,2) AS UNSIGNED) - 40
            WHEN CAST(SUBSTRING(m.pesel,3,2) AS UNSIGNED) BETWEEN 61 AND 72
                THEN CAST(SUBSTRING(m.pesel,3,2) AS UNSIGNED) - 60
        END, 2, '0'),
        '-',
        SUBSTRING(m.pesel, 5, 2)
    ), '%Y-%m-%d')                          AS nowa_data_urodzenia,

    IF(CAST(SUBSTRING(m.pesel,10,1) AS UNSIGNED) % 2 = 1, 'M', 'K')
                                            AS nowa_plec

FROM members m
WHERE m.pesel IS NOT NULL
  AND m.pesel != ''
  AND m.pesel REGEXP '^[0-9]{11}$'
  AND (
      -- cyfra kontrolna: suma cyfr * wagi mod 10 = 0
      (   CAST(SUBSTRING(m.pesel, 1,1) AS UNSIGNED) * 1
        + CAST(SUBSTRING(m.pesel, 2,1) AS UNSIGNED) * 3
        + CAST(SUBSTRING(m.pesel, 3,1) AS UNSIGNED) * 7
        + CAST(SUBSTRING(m.pesel, 4,1) AS UNSIGNED) * 9
        + CAST(SUBSTRING(m.pesel, 5,1) AS UNSIGNED) * 1
        + CAST(SUBSTRING(m.pesel, 6,1) AS UNSIGNED) * 3
        + CAST(SUBSTRING(m.pesel, 7,1) AS UNSIGNED) * 7
        + CAST(SUBSTRING(m.pesel, 8,1) AS UNSIGNED) * 9
        + CAST(SUBSTRING(m.pesel, 9,1) AS UNSIGNED) * 1
        + CAST(SUBSTRING(m.pesel,10,1) AS UNSIGNED) * 3
        + CAST(SUBSTRING(m.pesel,11,1) AS UNSIGNED) * 1
      ) % 10 = 0
  )
ORDER BY m.last_name, m.first_name;


-- ============================================================
-- KROK 2: ZAPIS — aktualizuje birth_date i gender
-- Pomija rekordy z błędnym PESEL (cyfra kontrolna, format)
-- ============================================================

UPDATE members m
JOIN (
    SELECT
        id,
        STR_TO_DATE(CONCAT(
            CASE
                WHEN CAST(SUBSTRING(pesel,3,2) AS UNSIGNED) BETWEEN 81 AND 92
                    THEN 1800 + CAST(SUBSTRING(pesel,1,2) AS UNSIGNED)
                WHEN CAST(SUBSTRING(pesel,3,2) AS UNSIGNED) BETWEEN  1 AND 12
                    THEN 1900 + CAST(SUBSTRING(pesel,1,2) AS UNSIGNED)
                WHEN CAST(SUBSTRING(pesel,3,2) AS UNSIGNED) BETWEEN 21 AND 32
                    THEN 2000 + CAST(SUBSTRING(pesel,1,2) AS UNSIGNED)
                WHEN CAST(SUBSTRING(pesel,3,2) AS UNSIGNED) BETWEEN 41 AND 52
                    THEN 2100 + CAST(SUBSTRING(pesel,1,2) AS UNSIGNED)
                WHEN CAST(SUBSTRING(pesel,3,2) AS UNSIGNED) BETWEEN 61 AND 72
                    THEN 2200 + CAST(SUBSTRING(pesel,1,2) AS UNSIGNED)
            END,
            '-',
            LPAD(CASE
                WHEN CAST(SUBSTRING(pesel,3,2) AS UNSIGNED) BETWEEN 81 AND 92
                    THEN CAST(SUBSTRING(pesel,3,2) AS UNSIGNED) - 80
                WHEN CAST(SUBSTRING(pesel,3,2) AS UNSIGNED) BETWEEN  1 AND 12
                    THEN CAST(SUBSTRING(pesel,3,2) AS UNSIGNED)
                WHEN CAST(SUBSTRING(pesel,3,2) AS UNSIGNED) BETWEEN 21 AND 32
                    THEN CAST(SUBSTRING(pesel,3,2) AS UNSIGNED) - 20
                WHEN CAST(SUBSTRING(pesel,3,2) AS UNSIGNED) BETWEEN 41 AND 52
                    THEN CAST(SUBSTRING(pesel,3,2) AS UNSIGNED) - 40
                WHEN CAST(SUBSTRING(pesel,3,2) AS UNSIGNED) BETWEEN 61 AND 72
                    THEN CAST(SUBSTRING(pesel,3,2) AS UNSIGNED) - 60
            END, 2, '0'),
            '-',
            SUBSTRING(pesel, 5, 2)
        ), '%Y-%m-%d')                                              AS nowa_data,
        IF(CAST(SUBSTRING(pesel,10,1) AS UNSIGNED) % 2 = 1, 'M', 'K') AS nowa_plec
    FROM members
    WHERE pesel IS NOT NULL
      AND pesel != ''
      AND pesel REGEXP '^[0-9]{11}$'
      AND (
          CAST(SUBSTRING(pesel, 1,1) AS UNSIGNED) * 1
        + CAST(SUBSTRING(pesel, 2,1) AS UNSIGNED) * 3
        + CAST(SUBSTRING(pesel, 3,1) AS UNSIGNED) * 7
        + CAST(SUBSTRING(pesel, 4,1) AS UNSIGNED) * 9
        + CAST(SUBSTRING(pesel, 5,1) AS UNSIGNED) * 1
        + CAST(SUBSTRING(pesel, 6,1) AS UNSIGNED) * 3
        + CAST(SUBSTRING(pesel, 7,1) AS UNSIGNED) * 7
        + CAST(SUBSTRING(pesel, 8,1) AS UNSIGNED) * 9
        + CAST(SUBSTRING(pesel, 9,1) AS UNSIGNED) * 1
        + CAST(SUBSTRING(pesel,10,1) AS UNSIGNED) * 3
        + CAST(SUBSTRING(pesel,11,1) AS UNSIGNED) * 1
      ) % 10 = 0
) p ON m.id = p.id
SET
    m.birth_date = p.nowa_data,
    m.gender     = p.nowa_plec
WHERE p.nowa_data IS NOT NULL;

-- Po wykonaniu możesz sprawdzić ilu rekordów dotyczyły zmiany:
-- SELECT ROW_COUNT() AS zaktualizowanych_rekordow;
