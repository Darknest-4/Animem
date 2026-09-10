CREATE TYPE "public"."airing_status" AS ENUM('airing', 'finished', 'upcoming', 'cancelled');--> statement-breakpoint
CREATE TYPE "public"."ban_scope" AS ENUM('ip', 'subnet', 'user', 'global');--> statement-breakpoint
CREATE TYPE "public"."ban_type" AS ENUM('automatic', 'manual', 'read_only');--> statement-breakpoint
CREATE TYPE "public"."job_status" AS ENUM('pending', 'reserved', 'completed', 'failed');--> statement-breakpoint
CREATE TYPE "public"."media_type" AS ENUM('tv', 'movie', 'ova', 'ona', 'special', 'music');--> statement-breakpoint
CREATE TYPE "public"."network_classification" AS ENUM('tor_exit', 'vpn', 'datacentre', 'residential');--> statement-breakpoint
CREATE TYPE "public"."password_algorithm" AS ENUM('argon2id', 'legacy_sha256');--> statement-breakpoint
CREATE TYPE "public"."release_kind" AS ENUM('sub', 'dub', 'raw');--> statement-breakpoint
CREATE TYPE "public"."rollout_strategy" AS ENUM('off', 'on', 'percentage', 'role_list', 'user_list', 'ip_list');--> statement-breakpoint
CREATE TYPE "public"."season" AS ENUM('winter', 'spring', 'summer', 'fall');--> statement-breakpoint
CREATE TYPE "public"."severity" AS ENUM('info', 'notice', 'warning', 'critical');--> statement-breakpoint
CREATE TYPE "public"."token_purpose" AS ENUM('email_verification', 'password_reset');--> statement-breakpoint
CREATE TYPE "public"."user_status" AS ENUM('pending_verification', 'active', 'suspended', 'deactivated');--> statement-breakpoint
CREATE TABLE "user_credentials" (
	"user_id" uuid PRIMARY KEY NOT NULL,
	"password_hash" text NOT NULL,
	"password_algorithm" "password_algorithm" DEFAULT 'argon2id' NOT NULL,
	"password_changed_at" timestamp with time zone,
	"failed_attempts" smallint DEFAULT 0 NOT NULL,
	"locked_until" timestamp with time zone,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL
);
--> statement-breakpoint
CREATE TABLE "users" (
	"id" uuid PRIMARY KEY NOT NULL,
	"username" varchar(32) NOT NULL,
	"username_canonical" varchar(32) NOT NULL,
	"email" varchar(254) NOT NULL,
	"email_canonical" varchar(254) NOT NULL,
	"status" "user_status" DEFAULT 'pending_verification' NOT NULL,
	"email_verified_at" timestamp with time zone,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL
);
--> statement-breakpoint
CREATE TABLE "one_time_tokens" (
	"id" uuid PRIMARY KEY NOT NULL,
	"user_id" uuid NOT NULL,
	"purpose" "token_purpose" NOT NULL,
	"token_hash" char(64) NOT NULL,
	"requested_ip" "inet",
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"expires_at" timestamp with time zone NOT NULL,
	"consumed_at" timestamp with time zone
);
--> statement-breakpoint
CREATE TABLE "sessions" (
	"id" uuid PRIMARY KEY NOT NULL,
	"user_id" uuid NOT NULL,
	"token_hash" char(64) NOT NULL,
	"created_ip" "inet" NOT NULL,
	"created_user_agent" varchar(512) DEFAULT '' NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"last_seen_at" timestamp with time zone DEFAULT now() NOT NULL,
	"expires_at" timestamp with time zone NOT NULL,
	"revoked_at" timestamp with time zone,
	"revoked_reason" varchar(64)
);
--> statement-breakpoint
CREATE TABLE "permissions" (
	"id" serial PRIMARY KEY NOT NULL,
	"slug" varchar(100) NOT NULL,
	"name" varchar(255) NOT NULL,
	"description" text,
	"category" varchar(64) DEFAULT 'general' NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL
);
--> statement-breakpoint
CREATE TABLE "role_permissions" (
	"role_id" integer NOT NULL,
	"permission_id" integer NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	CONSTRAINT "role_permissions_role_id_permission_id_pk" PRIMARY KEY("role_id","permission_id")
);
--> statement-breakpoint
CREATE TABLE "roles" (
	"id" serial PRIMARY KEY NOT NULL,
	"slug" varchar(64) NOT NULL,
	"name" varchar(255) NOT NULL,
	"description" text,
	"is_system" boolean DEFAULT false NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL
);
--> statement-breakpoint
CREATE TABLE "user_roles" (
	"user_id" uuid NOT NULL,
	"role_id" integer NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"granted_by" uuid,
	CONSTRAINT "user_roles_user_id_role_id_pk" PRIMARY KEY("user_id","role_id")
);
--> statement-breakpoint
CREATE TABLE "bans" (
	"id" uuid PRIMARY KEY NOT NULL,
	"scope" "ban_scope" NOT NULL,
	"ban_type" "ban_type" NOT NULL,
	"subject" varchar(128),
	"reason" text NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"expires_at" timestamp with time zone,
	"lifted_at" timestamp with time zone,
	"created_by" uuid
);
--> statement-breakpoint
CREATE TABLE "network_reputation" (
	"network" "cidr" PRIMARY KEY NOT NULL,
	"classification" "network_classification" NOT NULL,
	"asn" integer,
	"organisation" varchar(255),
	"created_at" timestamp with time zone DEFAULT now() NOT NULL
);
--> statement-breakpoint
CREATE TABLE "rate_limit_buckets" (
	"bucket_key" varchar(255) NOT NULL,
	"window_start" timestamp with time zone NOT NULL,
	"hits" integer DEFAULT 0 NOT NULL,
	"expires_at" timestamp with time zone NOT NULL,
	CONSTRAINT "rate_limit_buckets_bucket_key_window_start_pk" PRIMARY KEY("bucket_key","window_start")
);
--> statement-breakpoint
CREATE TABLE "security_events" (
	"id" uuid PRIMARY KEY NOT NULL,
	"event_type" varchar(64) NOT NULL,
	"severity" "severity" DEFAULT 'info' NOT NULL,
	"user_id" uuid,
	"ip" "inet" NOT NULL,
	"user_agent" varchar(512) DEFAULT '' NOT NULL,
	"method" varchar(10) DEFAULT '' NOT NULL,
	"path" varchar(512) DEFAULT '' NOT NULL,
	"risk_score" smallint DEFAULT 0 NOT NULL,
	"metadata" jsonb DEFAULT '{}'::jsonb NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL
);
--> statement-breakpoint
CREATE TABLE "feature_flag_audit" (
	"id" uuid PRIMARY KEY NOT NULL,
	"flag_key" varchar(100) NOT NULL,
	"changed_by" uuid,
	"before_state" jsonb DEFAULT '{}'::jsonb NOT NULL,
	"after_state" jsonb DEFAULT '{}'::jsonb NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL
);
--> statement-breakpoint
CREATE TABLE "feature_flags" (
	"id" serial PRIMARY KEY NOT NULL,
	"flag_key" varchar(100) NOT NULL,
	"name" varchar(255) NOT NULL,
	"description" text,
	"strategy" "rollout_strategy" DEFAULT 'off' NOT NULL,
	"rollout_percentage" smallint DEFAULT 0 NOT NULL,
	"payload" jsonb DEFAULT '{}'::jsonb NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL,
	"expires_at" timestamp with time zone
);
--> statement-breakpoint
CREATE TABLE "anime" (
	"id" uuid PRIMARY KEY NOT NULL,
	"mal_id" integer,
	"slug" varchar(200) NOT NULL,
	"title" varchar(400) NOT NULL,
	"title_english" varchar(400),
	"title_japanese" varchar(400),
	"synopsis" text,
	"media_type" "media_type" DEFAULT 'tv' NOT NULL,
	"status" "airing_status" DEFAULT 'finished' NOT NULL,
	"source" varchar(32),
	"age_rating" varchar(16),
	"episode_count" smallint,
	"season" "season",
	"season_year" smallint,
	"aired_from" date,
	"aired_to" date,
	"score" numeric(4, 2),
	"cover_url" varchar(512),
	"is_published" boolean DEFAULT false NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL,
	"created_by" uuid
);
--> statement-breakpoint
CREATE TABLE "anime_genres" (
	"anime_id" uuid NOT NULL,
	"genre_id" integer NOT NULL,
	CONSTRAINT "anime_genres_anime_id_genre_id_pk" PRIMARY KEY("anime_id","genre_id")
);
--> statement-breakpoint
CREATE TABLE "anime_studios" (
	"anime_id" uuid NOT NULL,
	"studio_id" integer NOT NULL,
	CONSTRAINT "anime_studios_anime_id_studio_id_pk" PRIMARY KEY("anime_id","studio_id")
);
--> statement-breakpoint
CREATE TABLE "genres" (
	"id" serial PRIMARY KEY NOT NULL,
	"slug" varchar(64) NOT NULL,
	"name_en" varchar(128) NOT NULL,
	"name_hu" varchar(128)
);
--> statement-breakpoint
CREATE TABLE "studios" (
	"id" serial PRIMARY KEY NOT NULL,
	"slug" varchar(64) NOT NULL,
	"name" varchar(160) NOT NULL
);
--> statement-breakpoint
CREATE TABLE "anime_uploaders" (
	"anime_id" uuid NOT NULL,
	"uploader_id" uuid NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	CONSTRAINT "anime_uploaders_anime_id_uploader_id_pk" PRIMARY KEY("anime_id","uploader_id")
);
--> statement-breakpoint
CREATE TABLE "uploaders" (
	"id" uuid PRIMARY KEY NOT NULL,
	"slug" varchar(64) NOT NULL,
	"name" varchar(160) NOT NULL,
	"description" text,
	"website_url" varchar(512),
	"facebook_url" varchar(512),
	"video_url" varchar(512),
	"email" varchar(254),
	"is_active" boolean DEFAULT true NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL
);
--> statement-breakpoint
CREATE TABLE "episode_releases" (
	"id" uuid PRIMARY KEY NOT NULL,
	"episode_id" uuid NOT NULL,
	"uploader_id" uuid NOT NULL,
	"language" varchar(8) DEFAULT 'hu' NOT NULL,
	"kind" "release_kind" DEFAULT 'sub' NOT NULL,
	"host" varchar(64) NOT NULL,
	"url" varchar(1024) NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"created_by" uuid
);
--> statement-breakpoint
CREATE TABLE "episode_view_counts" (
	"episode_id" uuid PRIMARY KEY NOT NULL,
	"views" bigint DEFAULT 0 NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL
);
--> statement-breakpoint
CREATE TABLE "episodes" (
	"id" uuid PRIMARY KEY NOT NULL,
	"anime_id" uuid NOT NULL,
	"number" smallint NOT NULL,
	"title" varchar(400),
	"title_japanese" varchar(400),
	"synopsis" text,
	"aired_on" date,
	"duration_seconds" integer,
	"is_published" boolean DEFAULT false NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL,
	"created_by" uuid
);
--> statement-breakpoint
CREATE TABLE "jobs" (
	"id" uuid PRIMARY KEY NOT NULL,
	"queue" varchar(64) DEFAULT 'default' NOT NULL,
	"name" varchar(128) NOT NULL,
	"payload" jsonb DEFAULT '{}'::jsonb NOT NULL,
	"attempts" smallint DEFAULT 0 NOT NULL,
	"max_attempts" smallint DEFAULT 5 NOT NULL,
	"available_at" timestamp with time zone DEFAULT now() NOT NULL,
	"reserved_at" timestamp with time zone,
	"completed_at" timestamp with time zone,
	"failed_at" timestamp with time zone,
	"last_error" text,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL
);
--> statement-breakpoint
ALTER TABLE "user_credentials" ADD CONSTRAINT "user_credentials_user_id_users_id_fk" FOREIGN KEY ("user_id") REFERENCES "public"."users"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "one_time_tokens" ADD CONSTRAINT "one_time_tokens_user_id_users_id_fk" FOREIGN KEY ("user_id") REFERENCES "public"."users"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "sessions" ADD CONSTRAINT "sessions_user_id_users_id_fk" FOREIGN KEY ("user_id") REFERENCES "public"."users"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "role_permissions" ADD CONSTRAINT "role_permissions_role_id_roles_id_fk" FOREIGN KEY ("role_id") REFERENCES "public"."roles"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "role_permissions" ADD CONSTRAINT "role_permissions_permission_id_permissions_id_fk" FOREIGN KEY ("permission_id") REFERENCES "public"."permissions"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "user_roles" ADD CONSTRAINT "user_roles_user_id_users_id_fk" FOREIGN KEY ("user_id") REFERENCES "public"."users"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "user_roles" ADD CONSTRAINT "user_roles_role_id_roles_id_fk" FOREIGN KEY ("role_id") REFERENCES "public"."roles"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "user_roles" ADD CONSTRAINT "user_roles_granted_by_users_id_fk" FOREIGN KEY ("granted_by") REFERENCES "public"."users"("id") ON DELETE set null ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "bans" ADD CONSTRAINT "bans_created_by_users_id_fk" FOREIGN KEY ("created_by") REFERENCES "public"."users"("id") ON DELETE set null ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "security_events" ADD CONSTRAINT "security_events_user_id_users_id_fk" FOREIGN KEY ("user_id") REFERENCES "public"."users"("id") ON DELETE set null ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "feature_flag_audit" ADD CONSTRAINT "feature_flag_audit_changed_by_users_id_fk" FOREIGN KEY ("changed_by") REFERENCES "public"."users"("id") ON DELETE set null ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "anime" ADD CONSTRAINT "anime_created_by_users_id_fk" FOREIGN KEY ("created_by") REFERENCES "public"."users"("id") ON DELETE set null ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "anime_genres" ADD CONSTRAINT "anime_genres_anime_id_anime_id_fk" FOREIGN KEY ("anime_id") REFERENCES "public"."anime"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "anime_genres" ADD CONSTRAINT "anime_genres_genre_id_genres_id_fk" FOREIGN KEY ("genre_id") REFERENCES "public"."genres"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "anime_studios" ADD CONSTRAINT "anime_studios_anime_id_anime_id_fk" FOREIGN KEY ("anime_id") REFERENCES "public"."anime"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "anime_studios" ADD CONSTRAINT "anime_studios_studio_id_studios_id_fk" FOREIGN KEY ("studio_id") REFERENCES "public"."studios"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "anime_uploaders" ADD CONSTRAINT "anime_uploaders_anime_id_anime_id_fk" FOREIGN KEY ("anime_id") REFERENCES "public"."anime"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "anime_uploaders" ADD CONSTRAINT "anime_uploaders_uploader_id_uploaders_id_fk" FOREIGN KEY ("uploader_id") REFERENCES "public"."uploaders"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "episode_releases" ADD CONSTRAINT "episode_releases_episode_id_episodes_id_fk" FOREIGN KEY ("episode_id") REFERENCES "public"."episodes"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "episode_releases" ADD CONSTRAINT "episode_releases_uploader_id_uploaders_id_fk" FOREIGN KEY ("uploader_id") REFERENCES "public"."uploaders"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "episode_releases" ADD CONSTRAINT "episode_releases_created_by_users_id_fk" FOREIGN KEY ("created_by") REFERENCES "public"."users"("id") ON DELETE set null ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "episode_view_counts" ADD CONSTRAINT "episode_view_counts_episode_id_episodes_id_fk" FOREIGN KEY ("episode_id") REFERENCES "public"."episodes"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "episodes" ADD CONSTRAINT "episodes_anime_id_anime_id_fk" FOREIGN KEY ("anime_id") REFERENCES "public"."anime"("id") ON DELETE cascade ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "episodes" ADD CONSTRAINT "episodes_created_by_users_id_fk" FOREIGN KEY ("created_by") REFERENCES "public"."users"("id") ON DELETE set null ON UPDATE no action;--> statement-breakpoint
CREATE UNIQUE INDEX "users_username_canonical_key" ON "users" USING btree ("username_canonical");--> statement-breakpoint
CREATE UNIQUE INDEX "users_email_canonical_key" ON "users" USING btree ("email_canonical");--> statement-breakpoint
CREATE INDEX "users_created_at_idx" ON "users" USING btree ("created_at" DESC NULLS LAST);--> statement-breakpoint
CREATE INDEX "users_status_idx" ON "users" USING btree ("status") WHERE "users"."status" <> 'active';--> statement-breakpoint
CREATE UNIQUE INDEX "one_time_tokens_hash_key" ON "one_time_tokens" USING btree ("token_hash");--> statement-breakpoint
CREATE INDEX "one_time_tokens_user_purpose_idx" ON "one_time_tokens" USING btree ("user_id","purpose") WHERE "one_time_tokens"."consumed_at" IS NULL;--> statement-breakpoint
CREATE INDEX "one_time_tokens_expiry_idx" ON "one_time_tokens" USING btree ("expires_at");--> statement-breakpoint
CREATE UNIQUE INDEX "sessions_token_hash_key" ON "sessions" USING btree ("token_hash");--> statement-breakpoint
CREATE INDEX "sessions_user_active_idx" ON "sessions" USING btree ("user_id","last_seen_at" DESC NULLS LAST) WHERE "sessions"."revoked_at" IS NULL;--> statement-breakpoint
CREATE INDEX "sessions_expires_at_idx" ON "sessions" USING btree ("expires_at") WHERE "sessions"."revoked_at" IS NULL;--> statement-breakpoint
CREATE UNIQUE INDEX "permissions_slug_key" ON "permissions" USING btree ("slug");--> statement-breakpoint
CREATE INDEX "permissions_category_idx" ON "permissions" USING btree ("category");--> statement-breakpoint
CREATE INDEX "role_permissions_permission_idx" ON "role_permissions" USING btree ("permission_id");--> statement-breakpoint
CREATE UNIQUE INDEX "roles_slug_key" ON "roles" USING btree ("slug");--> statement-breakpoint
CREATE INDEX "user_roles_role_idx" ON "user_roles" USING btree ("role_id");--> statement-breakpoint
CREATE INDEX "bans_active_idx" ON "bans" USING btree ("scope","subject") WHERE "bans"."lifted_at" IS NULL;--> statement-breakpoint
CREATE INDEX "network_reputation_contains_idx" ON "network_reputation" USING gist ("network");--> statement-breakpoint
CREATE INDEX "rate_limit_buckets_expiry_idx" ON "rate_limit_buckets" USING btree ("expires_at");--> statement-breakpoint
CREATE INDEX "security_events_ip_type_time_idx" ON "security_events" USING btree ("ip","event_type","created_at" DESC NULLS LAST);--> statement-breakpoint
CREATE INDEX "security_events_user_time_idx" ON "security_events" USING btree ("user_id","created_at" DESC NULLS LAST);--> statement-breakpoint
CREATE INDEX "security_events_severity_idx" ON "security_events" USING btree ("severity","created_at" DESC NULLS LAST) WHERE "security_events"."severity" IN ('warning', 'critical');--> statement-breakpoint
CREATE INDEX "feature_flag_audit_key_idx" ON "feature_flag_audit" USING btree ("flag_key","created_at" DESC NULLS LAST);--> statement-breakpoint
CREATE UNIQUE INDEX "feature_flags_key_key" ON "feature_flags" USING btree ("flag_key");--> statement-breakpoint
CREATE INDEX "feature_flags_expiring_idx" ON "feature_flags" USING btree ("expires_at") WHERE "feature_flags"."expires_at" IS NOT NULL;--> statement-breakpoint
CREATE UNIQUE INDEX "anime_slug_key" ON "anime" USING btree ("slug");--> statement-breakpoint
CREATE UNIQUE INDEX "anime_mal_id_key" ON "anime" USING btree ("mal_id") WHERE "anime"."mal_id" IS NOT NULL;--> statement-breakpoint
CREATE INDEX "anime_published_idx" ON "anime" USING btree ("is_published","created_at" DESC NULLS LAST);--> statement-breakpoint
CREATE INDEX "anime_season_idx" ON "anime" USING btree ("season_year" DESC NULLS LAST,"season") WHERE "anime"."season_year" IS NOT NULL;--> statement-breakpoint
CREATE INDEX "anime_status_idx" ON "anime" USING btree ("status");--> statement-breakpoint
CREATE INDEX "anime_title_trgm_idx" ON "anime" USING gin ("title" gin_trgm_ops);--> statement-breakpoint
CREATE INDEX "anime_genres_genre_idx" ON "anime_genres" USING btree ("genre_id");--> statement-breakpoint
CREATE INDEX "anime_studios_studio_idx" ON "anime_studios" USING btree ("studio_id");--> statement-breakpoint
CREATE UNIQUE INDEX "genres_slug_key" ON "genres" USING btree ("slug");--> statement-breakpoint
CREATE UNIQUE INDEX "studios_slug_key" ON "studios" USING btree ("slug");--> statement-breakpoint
CREATE INDEX "anime_uploaders_uploader_idx" ON "anime_uploaders" USING btree ("uploader_id");--> statement-breakpoint
CREATE UNIQUE INDEX "uploaders_slug_key" ON "uploaders" USING btree ("slug");--> statement-breakpoint
CREATE INDEX "uploaders_name_idx" ON "uploaders" USING btree ("name");--> statement-breakpoint
CREATE UNIQUE INDEX "episode_releases_unique" ON "episode_releases" USING btree ("episode_id","uploader_id","language","kind");--> statement-breakpoint
CREATE INDEX "episode_releases_uploader_idx" ON "episode_releases" USING btree ("uploader_id","created_at" DESC NULLS LAST);--> statement-breakpoint
CREATE UNIQUE INDEX "episodes_anime_number_key" ON "episodes" USING btree ("anime_id","number");--> statement-breakpoint
CREATE INDEX "episodes_published_idx" ON "episodes" USING btree ("anime_id","number") WHERE "episodes"."is_published";--> statement-breakpoint
CREATE INDEX "jobs_reservable_idx" ON "jobs" USING btree ("queue","available_at") WHERE "jobs"."reserved_at" IS NULL AND "jobs"."completed_at" IS NULL AND "jobs"."failed_at" IS NULL;--> statement-breakpoint
CREATE INDEX "jobs_failed_idx" ON "jobs" USING btree ("failed_at" DESC NULLS LAST) WHERE "jobs"."failed_at" IS NOT NULL;