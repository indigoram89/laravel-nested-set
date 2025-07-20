#!/bin/bash

# Скрипт для создания нового релиза
# Использование: ./release.sh [major|minor|patch] "Описание релиза"

VERSION_TYPE=${1:-patch}
RELEASE_MESSAGE=${2:-"Новый релиз"}

# Получаем текущую версию из composer.json
CURRENT_VERSION=$(grep -o '"version": "[^"]*"' composer.json | grep -o '[0-9]\+\.[0-9]\+\.[0-9]\+')

if [ -z "$CURRENT_VERSION" ]; then
    echo "Не найдена версия в composer.json"
    exit 1
fi

# Разбираем версию
IFS='.' read -ra VERSION_PARTS <<< "$CURRENT_VERSION"
MAJOR=${VERSION_PARTS[0]}
MINOR=${VERSION_PARTS[1]}
PATCH=${VERSION_PARTS[2]}

# Увеличиваем версию
case $VERSION_TYPE in
    major)
        MAJOR=$((MAJOR + 1))
        MINOR=0
        PATCH=0
        ;;
    minor)
        MINOR=$((MINOR + 1))
        PATCH=0
        ;;
    patch)
        PATCH=$((PATCH + 1))
        ;;
    *)
        echo "Неверный тип версии. Используйте: major, minor или patch"
        exit 1
        ;;
esac

NEW_VERSION="$MAJOR.$MINOR.$PATCH"

echo "Обновление версии с $CURRENT_VERSION на $NEW_VERSION"

# Обновляем версию в composer.json
if [[ "$OSTYPE" == "darwin"* ]]; then
    # macOS
    sed -i '' "s/\"version\": \"$CURRENT_VERSION\"/\"version\": \"$NEW_VERSION\"/" composer.json
else
    # Linux
    sed -i "s/\"version\": \"$CURRENT_VERSION\"/\"version\": \"$NEW_VERSION\"/" composer.json
fi

# Коммитим изменения
git add composer.json
git commit -m "Обновление версии до $NEW_VERSION"

# Создаем тег
git tag -a "v$NEW_VERSION" -m "$RELEASE_MESSAGE"

# Пушим изменения и теги
git push origin main
git push origin "v$NEW_VERSION"

echo "✅ Релиз v$NEW_VERSION создан и отправлен!"
echo "📦 Packagist автоматически обновится через webhook"