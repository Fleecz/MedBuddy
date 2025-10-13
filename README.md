#Zum Thema Branches:
- bitte arbeitet mit branches & nicht auf der main branch
- legt diese dann passend zum feature an
- Syntax für eigene Branch und wechseln: git checkout -b name
    - nennt die 1. einfach wie euch und macht dann Unterbranches zu den passenden Funktionen
- aktuelle Branches sind anzeigbar mit git branch
- kann auch verzweigter Pfad sein
- commited darauf eure Änderungen & wenn das fertig ist macht ihr auf GitHub eine PullRequest mit Beschreibung und Reviewer
- sollten unterbranches in main eingearbeitet sein und diese überflüssig werden:
    git branch -d name
    git push origin -- delete name
##Branch aktualisieren per Terminal
- Unbedingt vor jedem Weiterarbeiten machen, um      
  Merge-Konflikte zu verhindern:
    - git fetch origin
    - git rebase origin/main
#Commiten auf GitHub im Terminal
- das Hinzufügen von Änderungen funktioniert so:
1. git add Dateiname (trackt die Datei bei git)
2. git commit -m "Nachricht hier" (Bringt die Datei in Stagingarea. Die Nachricht ist wichtig, sollte aber kurz gehalten werden)
3. Am Anfang git push origin (branchname)
4. sonst reicht git push
(git status ist hilfreich)
#Arbeit mit Pullrequests
- nach gemeinsamer Absprache fügen wir das dann zur main branch hinzu
- die main branch ist geschützt, und Pull Requests müssen erst akzeptiert werden
- base ist immer main
- compare ist die branch aus der ihr gearbeitet habt
#Sonstiges
- wählt bei watch "ignore" aus, dann bekommt ihr keine emails mehr bei Pull Requests
a

Leon ist ein sehr guter Autofahrer