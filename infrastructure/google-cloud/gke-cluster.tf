data "google_container_engine_versions" "eu-west3" {
  zone = "${var.GOOGLE_CLOUD_REGION}-a"
}
resource "google_container_cluster" "production-k8s-cluster" {
  name               = "production-k8s-cluster"
  zone               = "${var.GOOGLE_CLOUD_REGION}-a"
  min_master_version = "${data.google_container_engine_versions.eu-west3.latest_master_version}"
  node_version       = "${data.google_container_engine_versions.eu-west3.latest_node_version}"
  initial_node_count = 3

  master_auth {
    username = "admin"
    password = "IpexTestInfraCloud"
  }

  node_config {
    oauth_scopes = [
      "https://www.googleapis.com/auth/compute",
      "https://www.googleapis.com/auth/devstorage.read_only",
      "https://www.googleapis.com/auth/logging.write",
      "https://www.googleapis.com/auth/monitoring",
    ]

    # "f1-micro" -> the cheapest one
    # n1-standard-1
    # n1 -highcpu-4
    machine_type = "n1-highcpu-4"
  }

  provisioner "local-exec" {
    command = "gcloud auth activate-service-account terraform@ipex-test-infrastructure.iam.gserviceaccount.com --key-file ./ipex-test-infra.json && gcloud container clusters get-credentials test-infra --zone europe-west3-a --project ipex-test-infrastructure && kubectl create clusterrolebinding cluster-admin-binding --clusterrole cluster-admin --user $(gcloud config get-value account)"
    interpreter = [ "/bin/bash", "-c"]
  }
}
