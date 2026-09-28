#!/bin/sh
# Publie chaque paquet de packages/ dans son propre dépôt Git (roadmap §46), avec son historique :
# git subtree split extrait packages/<nom> (composer.json à la racine), puis le pousse.
#
#   tools/split-packages.sh <modèle d'URL> <branche> [tag]
#
# Le modèle contient {package}, remplacé par le nom du paquet, par exemple :
#   https://x-access-token:$SPLIT_TOKEN@github.com/NiangPro/niangpro-{package}.git
#   /tmp/depots/{package}.git            (dépôts locaux, pour vérifier sans rien publier)
# Utilisé par .github/workflows/split.yml à chaque push sur main et à chaque tag v*.
set -eu

url_template=$1
branch=$2
tag=${3:-}

cd "$(dirname "$0")/.."

for dir in packages/*/; do
    package=$(basename "$dir")
    url=$(printf '%s' "$url_template" | sed "s|{package}|$package|g")
    sha=$(git subtree split --prefix="packages/$package" HEAD 2>/dev/null)

    git push --quiet --force "$url" "$sha:refs/heads/$branch"

    if [ -n "$tag" ]; then
        git push --quiet --force "$url" "$sha:refs/tags/$tag"
    fi

    echo "✓ $package → $(printf '%s' "$url" | sed 's|//[^@]*@|//***@|') ($branch${tag:+, $tag}) ${sha%${sha#???????}}"
done
