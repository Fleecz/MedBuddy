# Arbeitsweise im Projekt
## Zum Thema Branches:
    -arbeitet mit eigenen branchen & nicht direkt auf der main branch
        - legt diese unter der branch mit eurem Namen zum passenden feature an
    -Syntax für eigene Branch: `git checkout -b <name>`
        - am Anfang einfach euren Namen
        - danach branches zu Funktionen
        - kann auch als Pfad angegeben werden
    - Syntax zum Anzeigen von Branchen: `git branch`
### Sollten unterbranches nicht mehr nötig sein:
    - lokales löschen: `git branch -d name`
    - löschen für git: `git push origin delete <name>`
### Branches aktualliseren:
    __Unbedingt vor jedem Arbeiten machen, um Merge-Konflikte zu verhindern:__
    1. `git switch main`
    2. `git pull`
    3. `git switch <branchname>`
    4. `git fetch origin`
    5. `git rebase origin/main`
## Commits
    -  commitet Änderungen nur in den dazugehörigen Branches, bis die Funktionalität fertig ist
    - macht danach eine PullRequest in Main
### Commiten im Terminal:   
    - `git add <Dateiname>` oder .
    - `git commit -m "Nachricht hier"`
        - achtet auf nützliche Nachrichten
    - am anfang: `git push origin <branchname>`
    - danach reicht: `git push`
    - nützlich: `git status`
## Arbeiten mit Pull-Requests:
    - nach gemeinsamer Absprache fügen wir Pull Requests zu Main hinzu
    - base ist immer main
    - compare ist immer die branch in der ihr gearbeitet habt
## Sonstiges:
    - wählt bei watch "ignore" aus, dann bekommt ihr keine emails mehr bei Pull-Requests