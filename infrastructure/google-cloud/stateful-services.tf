# Has to be tainted for new suffix. Not optiomal
resource "random_string" "postgres-name-suffix" {
  length  = 5
  special = false
  upper   = false
  number  = false
}

resource "google_sql_database_instance" "postgres" {
  name             = "shopsys-postgres-${random_string.postgres-name-suffix.result}"
  database_version = "POSTGRES_9_6"
  region           = "${var.GOOGLE_CLOUD_REGION}"

  settings {
    tier = "db-f1-micro"
  }
}

resource "google_sql_user" "shopsys" {
  name     = "shopsys"
  instance = "${google_sql_database_instance.postgres.name}"
  password = "changeme"
}

resource "google_sql_database" "shopsys-production" {
  name      = "shopsys-production"
  instance  = "${google_sql_database_instance.postgres.name}"
  charset   = "UTF8"
  collation = "en_US.UTF8"
}

resource "google_redis_instance" "redis" {
  name = "shopsys-redis"

  tier           = "STANDARD_HA"
  memory_size_gb = 1

  region                  = "${var.GOOGLE_CLOUD_REGION}"
  location_id             = "${var.GOOGLE_CLOUD_REGION}-a"
  alternative_location_id = "${var.GOOGLE_CLOUD_REGION}-b"

  redis_version = "REDIS_3_2"
}

resource "google_storage_bucket" "file-store" {
  name     = "shopsys-file-store-bucket"
  location = "EU"
}

data "google_service_account" "gcs-service-account" {
  account_id = "gcs-service-account"
}

resource "google_service_account_key" "gcs-service-account" {
  service_account_id = "${data.google_service_account.gcs-service-account.name}"
}

resource "kubernetes_namespace" "shopsys-production" {
  metadata {
    name = "shopsys-production"
  }
}

resource "kubernetes_secret" "gcs-service-account" {
  metadata {
    name      = "gcs-service-account"
    namespace = "${kubernetes_namespace.shopsys-production.id}"
  }

  data {
    service-account.json = "${base64decode(google_service_account_key.gcs-service-account.private_key)}"
  }
}

output "gcs-service-account-json-key" {
  value     = "${base64decode(google_service_account_key.gcs-service-account.private_key)}"
  sensitive = true
}
