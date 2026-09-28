#!/bin/sh
# Publie le squelette niangpro/niangpro (tools/build-skeleton.php) dans son dépôt miroir, avec un
# historique : le squelette est reconstruit par-dessus le contenu du miroir, et un commit n'est créé
# que s'il a réellement changé.
#
#   tools/publish-skeleton.sh <URL du dépôt> <branche> [tag]
#
# Exemples : https://x-access-token:$SPLIT_TOKEN@github.com/NiangPro/niangpro-skeleton.git
#            /tmp/depots/skeleton.git   (dépôt local, pour vérifier sans rien publier)
set -eu

url=$1
branch=$2
tag=${3:-}
root=$(cd "$(dirname "$0")/.." && pwd)
source_sha=$(git -C "$root" rev-parse --short HEAD)
work=$(mktemp -d)
trap 'rm -rf "$work"' EXIT

if git clone --quiet --branch "$branch" "$url" "$work/mirror" 2>/dev/null; then
    :
else
    git init --quiet "$work/mirror"
    git -C "$work/mirror" checkout --quiet -b "$branch"
fi

php "$root/tools/build-skeleton.php" "$work/build" > /dev/null

# Remplace tout le contenu du miroir (sauf .git) par le squelette construit.
find "$work/mirror" -mindepth 1 -maxdepth 1 ! -name .git -exec rm -rf {} +
cp -R "$work/build/." "$work/mirror/"

cd "$work/mirror"
git add -A

if git diff --cached --quiet; then
    echo "Squelette inchangé"
else
    git -c user.name="${GIT_AUTHOR_NAME:-NiangPro}" -c user.email="${GIT_AUTHOR_EMAIL:-noreply@github.com}" \
        commit --quiet -m "Squelette construit depuis NiangPro/niangpro@$source_sha"
    echo "✓ nouveau commit du squelette ($source_sha)"
fi

git push --quiet "$url" "HEAD:refs/heads/$branch"

if [ -n "$tag" ]; then
    git tag -f "$tag" > /dev/null
    git push --quiet --force "$url" "refs/tags/$tag"
    echo "✓ tag $tag"
fi
