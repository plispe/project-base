variable "GOOGLE_CLOUD_REGION" {
  type        = "string"
  description = "Google cloud region for project resources"
  default     = "europe-west4"                              # we have to switch to europe-west4 because of redis
}

variable "GOOGLE_CLOUD_PROJECT_ID" {
  type    = "string"
  default = "shopsys-test-infra"
}
