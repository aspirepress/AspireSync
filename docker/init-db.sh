psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
	CREATE USER aspiresync PASSWORD 'password';
	CREATE DATABASE aspiresync WITH OWNER = 'aspiresync';
EOSQL
