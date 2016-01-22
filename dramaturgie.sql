PRAGMA encoding = 'UTF-8';
PRAGMA page_size = 8192;

CREATE TABLE play (
  -- une pièce
  id      INTEGER, -- rowid auto
  code    TEXT,    -- nom de fichier sans extension, unique pour la base
  author  TEXT,    -- auteur
  title   TEXT,    -- titre
  year    INTEGER, -- année, reprise du nom de fichier, ou dans le XML
  acts    INTEGER, -- nombre d’actes, essentiellement 5, 3, 1 ; ajuster pour les prologues
  scenes  INTEGER, -- nombre de scènes
  verse   BOOLEAN, -- uniquement si majoritairement en vers, ne pas cocher si chanson mêlée à de la prose
  genre   TEXT,    -- comedy|tragedy
  sp      INTEGER, -- <sp> taille en répliques
  l       INTEGER, -- <l> taille en vers
  w       INTEGER, -- <w> (word) taille en mots
  c       INTEGER, -- <c> (char) taille en caractères
  PRIMARY KEY(id ASC)
);
CREATE UNIQUE INDEX play_code ON play(code);

CREATE TABLE act (
  -- un acte
  id      INTEGER, -- rowid auto
  play    TEXT,    -- code pièce
  code    TEXT,    -- code acte, unique pour la pièce
  n       INTEGER, -- numéro d’ordre dans la pièce
  label   TEXT,    -- intitulé affichabe
  type    TEXT,    -- type d’acte (prologue, interlude…)
  sp      INTEGER, -- <sp> taille en répliques
  l       INTEGER, -- <l> taille en vers
  ln      INTEGER, -- numéro du premier vers
  w       INTEGER, -- <w> (word) taille en mots
  wn      INTEGER, -- numéro du premier mot
  c       INTEGER, -- <c> (char) taille en caractères
  cn      INTEGER, -- numéro du premier caractère
  PRIMARY KEY(id ASC)
);
CREATE UNIQUE INDEX act_code ON act(play, code);
CREATE INDEX act_type ON act(type);



CREATE TABLE scene (
  -- une scène (référence pour la présence d’un rôle)
  id      INTEGER, -- rowid auto
  play    TEXT,    -- code pièce
  act     TEXT,    -- code acte
  code    TEXT,    -- code scene, unique pour la pièce
  n       INTEGER, -- numéro d’ordre dans l’acte
  label   TEXT,    -- intitulé affichabe
  type    TEXT,    -- type de scene (prologue, interlude…)
  sp      INTEGER, -- <sp> taille en répliques
  l       INTEGER, -- <l> taille en vers
  ln      INTEGER, -- numéro du premier vers
  w       INTEGER, -- <w> (word) taille en mots
  wn      INTEGER, -- numéro du premier mot
  c       INTEGER, -- <c> (char) taille en caractères
  cn      INTEGER, -- numéro du premier caractère
  PRIMARY KEY(id ASC)
);
CREATE UNIQUE INDEX scene_code ON scene(play, code);
CREATE INDEX scene_act ON scene(play, act);

CREATE TABLE role (
  -- un rôle
  id       INTEGER,  -- rowid auto
  play     TEXT,     -- code pièce
  code     TEXT,     -- code personne
  label    TEXT,     -- nom affichable
  title    TEXT,     -- description du rôle (mère de…, amant de…) tel que dans la source
  note     TEXT,     -- possibilité de description plus étendue
  rend     TEXT,     -- série de mots clés séparés d’espaces (male|female)? (cadet)
  sex      INTEGER,  -- 1: homme, 2: femme, null: ?, 0: asexué, 9: dieu, ISO 5218:2004
  age      TEXT,     -- (cadet|junior|senior|veteran)
  status   TEXT,     -- pour isoler les confidents, serviteurs, ou pédants
  targets  INTEGER,  -- nombre d’interlocuteurs
  sp       INTEGER,  -- <sp> taille en répliques
  l        INTEGER,  -- <l> taille en vers
  w        INTEGER,  -- <w> taille en mots
  c        INTEGER,  -- <c> taille en caractères
  PRIMARY KEY(id ASC)
);
CREATE UNIQUE INDEX role_who ON role(play, code);

CREATE TABLE sp (
  -- une réplique 
  id INTEGER,           -- rowid auto
  play         TEXT,    -- nom de fichier de la pièce sans extension
  act          TEXT,    -- identifiant d’acte dans le fichier
  scene        TEXT,    -- identifiant de scene dans le fichier
  code         TEXT,    -- identifiant de réplique dans le fichier
  source       TEXT,    -- code de personnage
  target       TEXT,    -- code de personnage
  l            INTEGER, -- <l> nombre de vers
  ln           INTEGER, -- numéro du premier vers
  w            INTEGER, -- <w> nombre de mots
  wn           INTEGER, -- numéro du premier mot
  c            INTEGER, -- <c> nombre de caractères
  cn           INTEGER, -- numéro du premier caractère
  text         TEXT,    -- texte, pour récup ultérieure ?
  PRIMARY KEY(id ASC)
);
CREATE UNIQUE INDEX sp_path ON sp(play, act, scene, code);
CREATE INDEX sp_source ON sp(play, source, target);
CREATE INDEX sp_target ON sp(play, target, source);
CREATE UNIQUE INDEX sp_cn ON sp(play, cn);
CREATE UNIQUE INDEX sp_wn ON sp(play, wn);
CREATE INDEX sp_ln ON sp(play, ln);

CREATE TRIGGER playDel
  -- si on supprime une pièce, supprimer la cascade qui en dépend
  BEFORE DELETE ON play
  FOR EACH ROW BEGIN
    DELETE FROM act WHERE act.play = OLD.code;
    DELETE FROM scene WHERE scene.play = OLD.code;
    DELETE FROM role WHERE role.play = OLD.code;
    DELETE FROM sp WHERE sp.play = OLD.code;
END;
