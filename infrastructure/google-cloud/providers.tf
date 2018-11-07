provider "google" {
  credentials = "${file("service-account.json")}"
  project     = "shopsys-test-infra"
  region      = "${var.GOOGLE_CLOUD_REGION}"
}