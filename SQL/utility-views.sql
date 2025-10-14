-- 1. View to list all VIEWS
CREATE VIEW list_views AS
SELECT
    TABLE_NAME AS view_name,
    TABLE_SCHEMA AS database_name,
    VIEW_DEFINITION AS definition,
    CHECK_OPTION,
    IS_UPDATABLE,
    DEFINER,
    SECURITY_TYPE
FROM information_schema.VIEWS
WHERE TABLE_SCHEMA = DATABASE();

-- 2. View to list all PROCEDURES
CREATE VIEW list_procedures AS
SELECT
    ROUTINE_NAME AS procedure_name,
    ROUTINE_SCHEMA AS database_name,
    ROUTINE_TYPE AS type,
    DTD_IDENTIFIER AS returns,
    ROUTINE_DEFINITION AS definition,
    CREATED,
    LAST_ALTERED,
    DEFINER,
    SECURITY_TYPE
FROM information_schema.ROUTINES
WHERE ROUTINE_SCHEMA = DATABASE()
AND ROUTINE_TYPE = 'PROCEDURE';

-- 3. View to list all FUNCTIONS
CREATE VIEW list_functions AS
SELECT
    ROUTINE_NAME AS function_name,
    ROUTINE_SCHEMA AS database_name,
    ROUTINE_TYPE AS type,
    DATA_TYPE AS returns,
    ROUTINE_DEFINITION AS definition,
    CREATED,
    LAST_ALTERED,
    DEFINER,
    SECURITY_TYPE
FROM information_schema.ROUTINES
WHERE ROUTINE_SCHEMA = DATABASE()
AND ROUTINE_TYPE = 'FUNCTION';

-- 4. View to list all TRIGGERS
CREATE VIEW list_triggers AS
SELECT
    TRIGGER_NAME AS trigger_name,
    TRIGGER_SCHEMA AS database_name,
    EVENT_MANIPULATION AS event_type,
    EVENT_OBJECT_TABLE AS table_name,
    ACTION_TIMING AS timing,
    ACTION_STATEMENT AS definition,
    CREATED,
    DEFINER
FROM information_schema.TRIGGERS
WHERE TRIGGER_SCHEMA = DATABASE();