import 'reflect-metadata';
import { test } from 'node:test';
import assert from 'node:assert/strict';
import type { FactoryProvider, ValueProvider } from '@nestjs/common';
import { Tripay } from '../src';
import { TRIPAY_CONFIG, TripayModule } from '../src/nestjs';

const config = { apiKey: 'k', privateKey: 'p', merchantCode: 'T0001' };

test('forRoot menyediakan Tripay dari config', () => {
  const mod = TripayModule.forRoot(config);
  assert.equal(mod.global, true);
  assert.deepEqual(mod.exports, [Tripay]);
  const [cfg, client] = mod.providers as [ValueProvider, FactoryProvider];
  assert.equal(cfg.provide, TRIPAY_CONFIG);
  assert.ok(client.useFactory(cfg.useValue) instanceof Tripay);
});

test('forRootAsync meneruskan inject dan isGlobal', async () => {
  const mod = TripayModule.forRootAsync({ inject: ['CONFIG'], useFactory: async () => config, isGlobal: false });
  assert.equal(mod.global, false);
  const [cfg, client] = mod.providers as [FactoryProvider, FactoryProvider];
  assert.deepEqual(cfg.inject, ['CONFIG']);
  assert.ok(client.useFactory(await cfg.useFactory()) instanceof Tripay);
});
