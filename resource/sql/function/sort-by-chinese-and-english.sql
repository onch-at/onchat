--DROP FUNCTION IF EXISTS sortByChineseAndEnglish;

CREATE FUNCTION `sortByChineseAndEnglish`(P_NAME VARCHAR(255)) RETURNS VARCHAR(255) CHARSET utf8mb4 DETERMINISTIC BEGIN
    DECLARE
        V_RETURN VARCHAR(255); DECLARE V_BOOL INT DEFAULT 0; DECLARE FIRST_VARCHAR VARCHAR(1);
    SET
        FIRST_VARCHAR = LEFT(CONVERT(P_NAME USING gbk),
        1);
    SELECT
        FIRST_VARCHAR REGEXP '[a-zA-Z]'
    INTO V_BOOL; IF V_BOOL = 1 THEN
SET
    V_RETURN = FIRST_VARCHAR; ELSE
SET
    V_RETURN = ELT(
        INTERVAL(
            CONV(
                HEX(
                    LEFT(CONVERT(P_NAME USING gbk),
                    1)
                ),
                16,
                10
            ),
            0xb0a1,
            0xb0c5,
            0xb2c1,
            0xb4ee,
            0xb6ea,
            0xb7a2,
            0xb8c1,
            0xb9fe,
            0xbbf7,
            0xbfa6,
            0xc0ac,
            0xc2e8,
            0xc4c3,
            0xc5b6,
            0xc5be,
            0xc6da,
            0xc8bb,
            0xc8f6,
            0xcbfa,
            0xcdda,
            0xcef4,
            0xd1b9,
            0xd4d1
        ),
        'A',
        'B',
        'C',
        'D',
        'E',
        'F',
        'G',
        'H',
        'J',
        'K',
        'L',
        'M',
        'N',
        'O',
        'P',
        'Q',
        'R',
        'S',
        'T',
        'W',
        'X',
        'Y',
        'Z'
    );
END IF; RETURN V_RETURN;
END