import { DynamicModule, Module, Provider } from '@nestjs/common';
import type { FactoryProvider, ModuleMetadata } from '@nestjs/common';
import { Tripay } from './client';
import type { TripayConfig } from './types';

/** Token injeksi config. Client bisa di-inject langsung lewat kelas `Tripay`. */
export const TRIPAY_CONFIG = Symbol('TRIPAY_CONFIG');

export interface TripayModuleAsyncOptions extends Pick<ModuleMetadata, 'imports'> {
  useFactory: (...args: any[]) => TripayConfig | Promise<TripayConfig>;
  inject?: FactoryProvider['inject'];
  /** Default `true`: tersedia di semua modul tanpa import ulang. */
  isGlobal?: boolean;
}

const clientProvider: Provider = {
  provide: Tripay,
  useFactory: (config: TripayConfig) => new Tripay(config),
  inject: [TRIPAY_CONFIG],
};

/**
 * @example
 * TripayModule.forRootAsync({
 *   inject: [ConfigService],
 *   useFactory: (c: ConfigService) => ({
 *     apiKey: c.getOrThrow('TRIPAY_API_KEY'),
 *     privateKey: c.getOrThrow('TRIPAY_PRIVATE_KEY'),
 *     merchantCode: c.getOrThrow('TRIPAY_MERCHANT_CODE'),
 *     mode: c.get('TRIPAY_MODE') === 'production' ? 'production' : 'sandbox',
 *   }),
 * })
 */
@Module({})
export class TripayModule {
  static forRoot(config: TripayConfig, isGlobal = true): DynamicModule {
    return {
      module: TripayModule,
      global: isGlobal,
      providers: [{ provide: TRIPAY_CONFIG, useValue: config }, clientProvider],
      exports: [Tripay],
    };
  }

  static forRootAsync(options: TripayModuleAsyncOptions): DynamicModule {
    return {
      module: TripayModule,
      global: options.isGlobal ?? true,
      imports: options.imports ?? [],
      providers: [
        { provide: TRIPAY_CONFIG, useFactory: options.useFactory, inject: options.inject ?? [] },
        clientProvider,
      ],
      exports: [Tripay],
    };
  }
}
