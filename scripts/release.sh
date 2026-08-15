#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

VERSION=$(tr -d '[:space:]' < VERSION)
IMAGE="ghcr.io/jrbranaa/simple-blog"

echo "Building and pushing ${IMAGE}:${VERSION} (+ latest)"

docker build \
  --build-arg VERSION="$VERSION" \
  -t "${IMAGE}:${VERSION}" \
  -t "${IMAGE}:latest" \
  .

docker push "${IMAGE}:${VERSION}"
docker push "${IMAGE}:latest"
