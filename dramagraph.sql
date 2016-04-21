PRAGMA encoding = 'UTF-8';
PRAGMA page_size = 8192;  -- blob optimisation https://www.sqlite.org/intern-v-extern-blob.html
PRAGMA foreign_keys = ON;
-- The VACUUM command may change the ROWIDs of entries in any tables that do not have an explicit INTEGER PRIMARY KEY
CREATE TABLE play (
  -- une pièce
  id         INTEGER, -- rowid auto
  code       TEXT,    -- nom de fichier sans extension, unique pour la base
  publisher  TEXT,    -- URL de la source XML
  identifier TEXT,    -- URL du site de référence
  author     TEXT,    -- auteur
  title      TEXT,    -- titre
  date       INTEGER, -- année pertinente
  created    INTEGER, -- année de création
  issued     INTEGER, -- année de publication
  roles      INTEGER, -- nombre de personnages en tout
  speakers   INTEGER, -- nombre de personnages parlants
  proles     INTEGER, -- presence totale de tous les personnage en nombre de signes
  pspeakers  INTEGER, -- présence totale des personnages parlants (Σ configuration(c*speakers))
  entries    INTEGER, -- ??? nombre total d’entrées, pour moyennes
  acts       INTEGER, -- nombre d’actes, essentiellement 5, 3, 1 ; ajuster pour les prologues
  scenes     INTEGER, -- nombre de scènes
  confs      INTEGER, -- nombre de scènes
  verse      BOOLEAN, -- uniquement si majoritairement en vers, ne pas cocher si chanson mêlée à de la prose
  genre      TEXT,    -- comedy|tragedy
  c          INTEGER, -- <c> (char) taille en caractères
  w          INTEGER, -- <w> (word) taille en mots
  l          INTEGER, -- <l> taille en vers
  sp         INTEGER, -- <sp> taille en répliques
  PRIMARY KEY(id ASC)
);
CREATE UNIQUE INDEX play_code ON play(code);

CREATE TABLE object (
  -- stockage de blobs pour les pièces, par exemple texte complet, tables des matières
  id       INTEGER, -- rowid auto
  play     INTEGER REFERENCES play(id),   -- la pièce à laquelle est attachée l’objet
  playcode INTEGER REFERENCES play(code), -- code de la pièce (raccourci)
  type     TEXT,    -- type d’objet
  code     TEXT,    -- ocde, au moins unique pour une pièce
  cont     BLOB,    -- contenu de l’objet
  PRIMARY  KEY(id ASC)
);
CREATE INDEX object_playcode ON object(playcode, type, code);

CREATE TABLE act (
  -- un acte
  id      INTEGER, -- rowid auto
  play    INTEGER REFERENCES play(id), -- rowid pièce
  code    TEXT,    -- code acte, unique pour la pièce
  n       INTEGER, -- numéro d’ordre dans la pièce
  label   TEXT,    -- intitulé affichabe
  type    TEXT,    -- type d’acte (prologue, interlude…)
  scenes  INTEGER, -- nombre de scène
  confs   INTEGER, -- nombre de configurations
  cn      INTEGER, -- numéro du premier caractère
  wn      INTEGER, -- numéro du premier mot
  ln      INTEGER, -- numéro du premier vers
  spn     INTEGER, -- numéro de répliques
  c       INTEGER, -- <c> (char) taille en caractères
  w       INTEGER, -- <w> (word) taille en mots
  l       INTEGER, -- <l> taille en vers
  sp      INTEGER, -- <sp> taille en répliques
  PRIMARY KEY(id ASC)
);
CREATE UNIQUE INDEX act_code ON act(play, code);
CREATE INDEX act_type ON act(type);



CREATE TABLE scene (
  -- une scène (référence pour la présence d’un rôle)
  id      INTEGER, -- rowid auto
  play    INTEGER REFERENCES play(id),   -- rowid pièce
  act     INTEGER REFERENCES act(id),    -- rowid acte
  code    TEXT,    -- code scene, unique pour la pièce
  n       INTEGER, -- numéro d’ordre dans l’acte
  label   TEXT,    -- intitulé affichabe
  type    TEXT,    -- type de scene (prologue, interlude…)
  confs   INTEGER, -- nombre de configurations
  cn      INTEGER, -- numéro du premier caractère
  wn      INTEGER, -- numéro du premier mot
  ln      INTEGER, -- numéro du premier vers
  spn     INTEGER, -- numéro de répliques
  c       INTEGER, -- <c> (char) taille en caractères
  w       INTEGER, -- <w> (word) taille en mots
  l       INTEGER, -- <l> taille en vers
  sp      INTEGER, -- <sp> taille en répliques
  PRIMARY KEY(id ASC)
);
CREATE UNIQUE INDEX scene_code ON scene(play, code);
CREATE INDEX scene_act ON scene(act);

CREATE TABLE configuration (
  -- une configuration est un état de la scène (personnages présents)
  id       INTEGER, -- rowid auto
  play     INTEGER REFERENCES play(id),-- id pièce
  act      INTEGER REFERENCES act(id), -- id acte
  scene    INTEGER REFERENCES scene(id), -- rowid scène
  code     TEXT,    -- code de conf (= @xml:id)
  n        INTEGER, -- numéro d’ordre dans la pièce
  label    TEXT,    -- liste de codes de personnage
  roles    INTEGER, -- nombre de rôles présents
  speakers INTEGER, -- nombre de rôles parlants
  cn       INTEGER, -- numéro du premier caractère
  wn       INTEGER, -- numéro du premier mot
  ln       INTEGER, -- numéro du premier vers
  spn      INTEGER, -- numéro de répliques
  c        INTEGER, -- <c> (char) taille en caractères
  w        INTEGER, -- <w> (word) taille en mots
  l        INTEGER, -- <l> taille en vers
  sp       INTEGER, -- <sp> taille en répliques
  PRIMARY KEY(id ASC)
);
CREATE UNIQUE INDEX configuration_code ON configuration(play, code);
CREATE INDEX configuration_act ON configuration(act);

CREATE TABLE role (
  -- un rôle
  id        INTEGER,  -- rowid auto
  play      INTEGER REFERENCES play(id), -- rowid de pièce
  ord       INTEGER,  -- ordre dans la distribution
  code      TEXT,     -- code personne
  label     TEXT,     -- nom affichable
  title     TEXT,     -- description du rôle (mère de…, amant de…) tel que dans la source
  note      TEXT,     -- possibilité de description plus étendue
  rend      TEXT,     -- série de mots clés séparés d’espaces (male|female)? (cadet)
  sex       INTEGER,  -- 1: homme, 2: femme, null: ?, 0: asexué, 9: dieu, ISO 5218:2004
  age       TEXT,     -- (cadet|junior|senior|veteran)
  status    TEXT,     -- pour isoler les confidents, serviteurs, ou pédants
  targets   INTEGER,  -- nombre de destinataires
  sources   INTEGER,  -- nombre d’émetteurs
  proles    INTEGER,  -- presence totale de tous les personnage en nombre de signes
  pspeakers INTEGER,  -- présence totale des personnages parlants
  confs     INTEGER,  -- nombre de configurations
  presence  INTEGER,  -- temps de présence (en caractères)
  entries   INTEGER,  -- nombre d’entrées en scène
  c         INTEGER,  -- out <c>, mombre de caractères dits
  w         INTEGER,  -- out <w>, mombre de mots dits
  l         INTEGER,  -- out <l>, nombre de vers dits
  sp        INTEGER,  -- out <sp>, nombre de répliques dites
  PRIMARY KEY(id ASC)
);
CREATE UNIQUE INDEX role_who ON role(play, code);
CREATE UNIQUE INDEX role_ord ON role(play, ord);
CREATE INDEX role_c ON role(c);
CREATE INDEX role_presence ON role(presence);

CREATE TABLE presence (
  -- Relation de présence entre une configuration et un rôle
  id       INTEGER,  -- rowid auto
  play     INTEGER REFERENCES play(id), -- id de pièce
  configuration INTEGER REFERENCES configuration(id), -- id de configuration
  role     INTEGER REFERENCES role(id), -- id de rôle
  type     TEXT,  -- type de présence (mort, inconscient…)
  c        INT, -- nombre de caractères prononcés par le rôle dans cette configuration
  PRIMARY KEY(id ASC)
);
CREATE INDEX presence_play ON presence(play);
CREATE UNIQUE INDEX presence_role ON presence(role, configuration);
CREATE UNIQUE INDEX presence_configuration ON presence(configuration, role);

CREATE TABLE stage (
  -- une didascalie
  id      INTEGER,  -- rowid auto
  play    INTEGER REFERENCES play(id), -- rowid pièce
  act     INTEGER REFERENCES act(id), -- rowid acte
  scene   INTEGER REFERENCES scene(id), -- rowid scène
  configuration  INTEGER REFERENCES configuration(id), -- rowid de configuration
  code    TEXT,    -- code de conf (= @xml:id)
  n       INTEGER, -- numéro d’ordre dans la pièce
  cn      INTEGER, -- numéro de caractère dans les répliques
  wn      INTEGER, -- numéro de mots dans les répliques
  ln      INTEGER, -- numéro de vers courant
  c       INTEGER, -- nombre de caractères dans la didascalie
  w       INTEGER, -- nombre de mots dans la didascalie
  text    TEXT,    -- texte, pour récup ultérieure ?
  PRIMARY KEY(id ASC)
);
CREATE UNIQUE INDEX stage_code ON stage(play, code);
CREATE INDEX stage_act ON stage(act);
CREATE INDEX stage_scene ON stage(scene);
CREATE INDEX stage_configuration ON stage(configuration);

CREATE TABLE sp (
  -- une réplique 
  id INTEGER,           -- rowid auto
  play          INTEGER REFERENCES play(id),    -- id pièce dans la base
  act           INTEGER REFERENCES act(id),     -- identifiant d’acte
  scene         INTEGER REFERENCES scene(id),   -- id de scene
  configuration INTEGER REFERENCES configuration(id),   -- id de configuration
  role          INTEGER REFERENCES role(id),   -- personnage qui parle
  code          TEXT,    -- identifiant de réplique dans le fichier
  cn            INTEGER, -- numéro du premier caractère
  wn            INTEGER, -- numéro du premier mot
  ln            INTEGER, -- numéro du premier vers
  c             INTEGER, -- <c> nombre de caractères
  w             INTEGER, -- <w> nombre de mots
  l             INTEGER, -- <l> nombre de vers
  text          TEXT,    -- texte, pour récup ultérieure ?
  PRIMARY KEY(id ASC)
);
CREATE UNIQUE INDEX sp_code ON sp(play, code);
CREATE UNIQUE INDEX sp_cn ON sp(play, cn);
CREATE UNIQUE INDEX sp_wn ON sp(play, wn);
CREATE INDEX sp_play ON sp(play);
CREATE INDEX sp_act ON sp(act);
CREATE INDEX sp_scene ON sp(scene);
CREATE INDEX sp_configuration ON sp(configuration);
CREATE INDEX sp_role ON sp(role);
CREATE INDEX sp_ln ON sp(play, ln);

CREATE TABLE edge (
  -- destinataires d’une réplique
  id INTEGER,           -- rowid auto
  play INTEGER REFERENCES play(id), -- id pièce dans la base
  source INTEGER REFERENCES role(id), -- id de role = source
  target INTEGER REFERENCES role(id), -- id de role = target
  sp   INTEGER REFERENCES sp(id),   -- id de réplique = source
  PRIMARY KEY(id ASC)
);
CREATE INDEX edge_play ON edge(play);
CREATE INDEX edge_sp ON edge(sp);
CREATE INDEX edge_source ON edge(source, target);
CREATE INDEX edge_target ON edge(target, source);


CREATE TRIGGER playDel
  -- si on supprime une pièce, supprimer la cascade qui en dépend
  BEFORE DELETE ON play
  FOR EACH ROW BEGIN
    DELETE FROM object WHERE object.play = OLD.id;
    DELETE FROM act WHERE act.play = OLD.id;
    DELETE FROM scene WHERE scene.play = OLD.id;
    DELETE FROM configuration WHERE configuration.play = OLD.id;
    DELETE FROM role WHERE role.play = OLD.id;
    DELETE FROM presence WHERE presence.play = OLD.id;
    DELETE FROM sp WHERE sp.play = OLD.id;
    DELETE FROM edge WHERE edge.play = OLD.id;
    DELETE FROM stage WHERE stage.play = OLD.id;
END;
