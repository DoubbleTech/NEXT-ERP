import Form from '@/components/Home/Form'
import Logo from '@/components/Home/logo'
import React from 'react'

const Page = () => {
  
  return (
    <section className='border-2   h-full my-10 max-w-6xl mx-auto shadow-[0_0_25px_rgba(0,0,0,0.2)] rounded-xl'>
      <div className="flex w-full gap-20">
        {/* Logo */}
        <div className='w-[500px] '>
          <Logo />
        </div>

        {/* Form */}
        <div className=' flex-1 pr-10'>
          <Form />
        </div>
      </div>
    </section>
  )
}

export default Page
