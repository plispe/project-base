resource "google_sql_database_instance" "postgres" {
  name = "shopsys-postgres"
  database_version = "POSTGRES_9_6"
  region = "${var.GOOGLE_CLOUD_REGION}"

  settings {
    tier = "db-f1-micro"
  }
}

resource "google_redis_instance" "redis" {

    name                = "shopsys-redis"

    tier                = "STANDARD_HA"
    memory_size_gb      = 1


    region              = "${var.GOOGLE_CLOUD_REGION}"
    location_id             = "${var.GOOGLE_CLOUD_REGION}-a"
    alternative_location_id = "${var.GOOGLE_CLOUD_REGION}-b"

    redis_version       = "REDIS_3_2"
}