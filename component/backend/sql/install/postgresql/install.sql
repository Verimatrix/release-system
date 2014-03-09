CREATE TABLE IF NOT EXISTS "#__ars_vgroups" (
    "id" serial NOT NULL,
    "title" varchar(255) NOT NULL,
    "description" text,
    "created" timestamp without time zone NOT NULL,
    "created_by" int NOT NULL DEFAULT '0',
    "modified" timestamp without time zone NOT NULL DEFAULT '1970-01-01 00:00:00',
    "modified_by" int NOT NULL DEFAULT '0',
    "checked_out" int NOT NULL DEFAULT '0',
    "checked_out_time" timestamp without time zone NOT NULL DEFAULT '1970-01-01 00:00:00',
    "ordering" bigint NOT NULL DEFAULT '0',
    "published" int NOT NULL DEFAULT '1',
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS "#__ars_categories" (
    "id" serial NOT NULL,
    "title" varchar(255) NOT NULL,
    "alias" varchar(255) NOT NULL,
    "description" text,
    "type" TEXT CHECK ("type" IN ('normal', 'bleedingedge')) NOT NULL DEFAULT 'normal',
    "groups" varchar(255) DEFAULT NULL,
    "directory" varchar(255) NOT NULL DEFAULT 'arsrepo',
	  "vgroup_id" bigint NOT NULL DEFAULT '0',
    "created" timestamp without time zone NOT NULL,
    "created_by" int NOT NULL DEFAULT '0',
    "modified" timestamp without time zone NOT NULL DEFAULT '1970-01-01 00:00:00',
    "modified_by" int NOT NULL DEFAULT '0',
    "checked_out" int NOT NULL DEFAULT '0',
    "checked_out_time" timestamp without time zone NOT NULL DEFAULT '1970-01-01 00:00:00',
    "ordering" bigint NOT NULL DEFAULT '0',
    "access" int NOT NULL DEFAULT '0',
    "show_unauth_links" smallint NOT NULL DEFAULT '0',
    "redirect_unauth" VARCHAR( 255 ) NOT NULL,
    "published" int NOT NULL DEFAULT '1',
	"language" char(7) NOT NULL DEFAULT '*',
    PRIMARY KEY ("id")
);

CREATE INDEX "#__ars_categories_published" ON "#__ars_categories" ("published");

CREATE TABLE IF NOT EXISTS "#__ars_releases" (
    "id" serial NOT NULL,
    "category_id" bigint NOT NULL,
    "version" VARCHAR(255) NOT NULL,
    "alias" VARCHAR(255) NOT NULL,
    "maturity" TEXT CHECK ("maturity" IN ('alpha','beta','rc','stable')) NOT NULL DEFAULT 'beta',
    "description" text NULL,
    "notes" TEXT NULL,
    "groups" varchar(255) DEFAULT NULL,
    "hits" bigint NOT NULL DEFAULT 0,
    "created" timestamp without time zone NOT NULL DEFAULT '1970-01-01 00:00:00',
    "created_by" int NOT NULL DEFAULT '0',
    "modified" timestamp without time zone NOT NULL DEFAULT '1970-01-01 00:00:00',
    "modified_by" int NOT NULL DEFAULT '0',
    "checked_out" int NOT NULL DEFAULT '0',
    "checked_out_time" timestamp without time zone NOT NULL DEFAULT '1970-01-01 00:00:00',
    "ordering" bigint NOT NULL,
    "access" int NOT NULL DEFAULT '0',
    "show_unauth_links" smallint NOT NULL DEFAULT '0',
    "redirect_unauth" VARCHAR( 255 ) NOT NULL,
    "published" smallint NOT NULL DEFAULT '1',
	  "language" char(7) NOT NULL DEFAULT '*',
	  PRIMARY KEY ("id")
);

CREATE INDEX "#__ars_releases_category_id" ON "#__ars_releases" ("category_id");
CREATE INDEX "#__ars_releases_published" ON "#__ars_releases" ("published");

CREATE TABLE IF NOT EXISTS "#__ars_items" (
    "id" serial NOT NULL,
    "release_id" bigint NOT NULL,
    "title" VARCHAR(255) NOT NULL,
    "alias" VARCHAR(255) NOT NULL,
    "description" text NOT NULL,
    "type" TEXT CHECK ("type" IN ('link', 'file')),
    "filename" VARCHAR(255) NULL DEFAULT '',
    "url" VARCHAR(255) NULL DEFAULT '',
    "updatestream" bigint DEFAULT NULL,
    "md5" varchar(32) DEFAULT NULL,
    "sha1" varchar(64) DEFAULT NULL,
    "filesize" int DEFAULT NULL,
    "groups" varchar(255) DEFAULT NULL,
    "hits" bigint NOT NULL DEFAULT 0,
    "created" timestamp without time zone NOT NULL,
    "created_by" int NOT NULL DEFAULT '0',
    "modified" timestamp without time zone NOT NULL DEFAULT '1970-01-01 00:00:00',
    "modified_by" int NOT NULL DEFAULT '0',
    "checked_out" int NOT NULL DEFAULT '0',
    "checked_out_time" timestamp without time zone NOT NULL DEFAULT '1970-01-01 00:00:00',
    "ordering" bigint NOT NULL,
    "access" int NOT NULL DEFAULT '0',
    "show_unauth_links" smallint NOT NULL DEFAULT '0',
    "redirect_unauth" VARCHAR( 255 ) NOT NULL,
    "published" smallint NOT NULL DEFAULT '1',
	"language" char(7) NOT NULL DEFAULT '*',
    "environments" varchar(255) DEFAULT NULL,
	PRIMARY KEY ("id")
);

CREATE INDEX "#__ars_items_release_id" ON "#__ars_items" ("release_id");
CREATE INDEX "#__ars_items_updatestream" ON "#__ars_items" ("updatestream");
CREATE INDEX "#__ars_items_published" ON "#__ars_items" ("published");

CREATE TABLE IF NOT EXISTS "#__ars_log" (
    "id" serial NOT NULL,
    "user_id" bigint NOT NULL,
    "item_id" bigint NOT NULL,
    "accessed_on" timestamp without time zone NOT NULL,
    "referer" VARCHAR(255) NOT NULL,
    "ip" VARCHAR(255) NOT NULL,
    "country" VARCHAR(3) NOT NULL,
    "authorized" smallint NOT NULL DEFAULT '1',
	PRIMARY KEY ("id")
);

CREATE INDEX "ars_log_accessed" ON "#__ars_log" ("accessed_on");
CREATE INDEX "ars_log_authorized" ON "#__ars_log" ("authorized");
CREATE INDEX "ars_log_itemid" ON "#__ars_log" ("item_id");
CREATE INDEX "ars_log_userid" ON "#__ars_log" ("user_id");

CREATE TABLE IF NOT EXISTS "#__ars_updatestreams" (
	"id" serial NOT NULL,
	"name" VARCHAR(255) NOT NULL,
	"alias" VARCHAR(255) NOT NULL,
  "type" TEXT CHECK ("type" IN ('components','libraries','modules','packages','plugins','files','templates')) NOT NULL DEFAULT 'components',
	"element" VARCHAR(255) NOT NULL,
	"category" bigint NOT NULL,
	"packname" VARCHAR(255),
	"client_id" int NOT NULL DEFAULT '1',
	"folder" varchar(255) DEFAULT '',
	"jedid" bigint NOT NULL,
	"created" timestamp without time zone NOT NULL,
	"created_by" int NOT NULL DEFAULT '0',
	"modified" timestamp without time zone NOT NULL DEFAULT '1970-01-01 00:00:00',
	"modified_by" int NOT NULL DEFAULT '0',
	"checked_out" int NOT NULL DEFAULT '0',
	"checked_out_time" timestamp without time zone NOT NULL DEFAULT '1970-01-01 00:00:00',
	"published" int NOT NULL DEFAULT '1',
	PRIMARY KEY ("id")
);

CREATE INDEX "#__ars_updatestreams_published" ON "#__ars_updatestreams" ("published");
CREATE INDEX "#__ars_updatestreams_jedid" ON "#__ars_updatestreams" ("jedid");

CREATE TABLE IF NOT EXISTS "#__ars_autoitemdesc" (
	"id" serial NOT NULL,
	"category" bigint NOT NULL,
	"packname" varchar(255) DEFAULT NULL,
	"title" varchar(255) NOT NULL,
	"description" text NOT NULL,
	"environments" varchar(100) DEFAULT NULL,
	"published" int NOT NULL DEFAULT '1',
	PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS "#__ars_environments" (
  "id" serial NOT NULL,
  "title" varchar(100) NOT NULL DEFAULT '',
  "xmltitle" varchar(20) NOT NULL DEFAULT '1.0',
  "icon" varchar(255) DEFAULT NULL,
  PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS "#__ars_dlidlabels" (
  "ars_dlidlabel_id" serial NOT NULL,
  "user_id" bigint NOT NULL,
  "label" varchar(255) NOT NULL DEFAULT '',
  "enabled" smallint NOT NULL DEFAULT '1',
  "created_by" bigint NOT NULL DEFAULT '0',
  "created_on" timestamp without time zone NOT NULL DEFAULT '1970-01-01 00:00:00',
  "modified_by" bigint NOT NULL DEFAULT '0',
  "modified_on" timestamp without time zone NOT NULL DEFAULT '1970-01-01 00:00:00',
  PRIMARY KEY ("ars_dlidlabel_id")
);