data "google_container_engine_versions" "k8s-versions-available" {
  zone = "${var.GOOGLE_CLOUD_REGION}-a"
}

resource "google_container_cluster" "production-k8s-cluster" {
  name               = "production-k8s-cluster"
  zone               = "${var.GOOGLE_CLOUD_REGION}-a"
  min_master_version = "${data.google_container_engine_versions.k8s-versions-available.latest_master_version}"
  node_version       = "${data.google_container_engine_versions.k8s-versions-available.latest_node_version}"
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

  addons_config {
    http_load_balancing {
      disabled = true
    }

    horizontal_pod_autoscaling {
      disabled = true
    }
  }

  provisioner "local-exec" {
    command     = "gcloud container clusters get-credentials ${google_container_cluster.production-k8s-cluster.name} --zone ${var.GOOGLE_CLOUD_REGION}-a && kubectl create clusterrolebinding cluster-admin-binding --clusterrole cluster-admin --user $(gcloud config get-value account)"
    interpreter = ["/bin/bash", "-c"]
  }
}
